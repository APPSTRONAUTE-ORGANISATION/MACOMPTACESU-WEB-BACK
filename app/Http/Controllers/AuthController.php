<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileImageRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\ApiPasswordResetNotification;
use App\Traits\SaveFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use illuminate\Support\Str;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Customer;

class AuthController extends Controller
{
    use SaveFileTrait;

    public function Login(LoginRequest $request)
    {
        $user = User::whereEmail($request->email)->first();

        if (!Hash::check($request->password, $user->password) || !$user->active) {
            return response()->json([
                'message' => 'Identifiants invalides',
            ], 401);
        }
        
        $user->tokens()->delete();
        $expires_at = now()->addDay();

        $token = $user->createToken('token', ['*'], $expires_at)->plainTextToken;

        if ($user->stripe_id !== 'admin') {

            $hasActiveSubscription = $user->subscriptions()->where('stripe_status', 'active')->exists();
            $subscription = $user->subscriptions()
            ->whereNotNull('trial_ends_at')
            ->latest('trial_ends_at')
            ->first();
            $isTrialValid = $subscription && $subscription->trial_ends_at->isFuture();

            // Vérifie l'abonnement directement sur Stripe
            $stripeSubscriptionType = null;
                Stripe::setApiKey(config('cashier.secret'));

                $customer = Customer::retrieve($user->stripe_id);
                $subscriptions = Subscription::all([
                    'customer' => $customer->id,
                    'status' => 'active',
                    'limit' => 1,
                ]);

                if (count($subscriptions->data) > 0) {
                    $stripeSubscription = $subscriptions->data[0];
                    $stripeSubscriptionType = $stripeSubscription->items->data[0]->price->nickname ?? 'Non défini';
                }

            if (!$isTrialValid && $user->subscriptions()->where('type', 'gratuit')->exists() && !$stripeSubscriptionType) {
                return response()->json([
                    'message' => 'Votre essai gratuit s\'est terminé le ' . Carbon::parse($user->subscriptions()->latest()->first()->trial_ends_at)->format('d-m-Y') . '. Pour continuer, veuillez souscrire à un abonnement',
                    'token' => $token,
                    'user' => new UserResource($user),
                    'trialUsed' => $user->subscriptions()->where('type', 'gratuit')->exists(),
                ], 401);
            }

            if (!$hasActiveSubscription && !$stripeSubscriptionType) {
                return response()->json([
                    'message' => 'Vous devez vous abonner pour continuer',
                    'abonnement_stripe' => $stripeSubscriptionType,
                    'token' => $token,
                    'user' => new UserResource($user),
                    'trialUsed' => $user->subscriptions()->where('type', 'gratuit')->exists(),
                ], 401);
            }

            // $user->tokens()->delete();
            // $expires_at = now()->addDay();

            // $token = $user->createToken('token', ['*'], $expires_at)->plainTextToken;
        }
        return response()->json([
            'token' => $token,
            'expires_at' => $expires_at,
            'user' => new UserResource($user),
            'trialUsed' => $user->subscriptions()->where('type', 'gratuit')->exists(),
            // 'trial_ends_at' => optional($user->subscriptions()->latest()->first())->trial_ends_at,
        ]);
    }

    public function Logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Déconnecté avec succès',
        ]);
    }

    public function Register(RegisterRequest $request)
    {
        $user = User::create(array_merge($request->validated(), [
            'password' => bcrypt($request->validated('password')),
            'profile_image' => $request->file('profile_image') ? $this->save_file($request->file('profile_image'), 'profile_images') : null
        ]));

        $user->assignRole('client');

        $user->createAsStripeCustomer();

        $user->tokens()->delete();
        $expires_at = now()->addDay();

        $token = $user->createToken('token', ['*'], $expires_at)->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => $expires_at,
            'user' => new UserResource($user),
        ]);
    }

    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $search = $request->search;

        $query = User::query();

        $query->with([
            'Subscriptions',
        ]);

        $query->when($search, function ($query) use ($search) {
            $query->where('first_name', 'LIKE', "%$search%");
            $query->orWhere('last_name', 'LIKE', "%$search%");
            $query->orWhere('country', 'LIKE', "%$search%");
            $query->orWhere('phone', 'LIKE', "%$search%");
            $query->orWhere('job', 'LIKE', "%$search%");
            $query->orWhere('email', 'LIKE', "%$search%");
        });

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => UserResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
        ]);
    }

    public function show(User $user)
    {
        $user->load([
            'Subscriptions'
        ]);
        return response()->json(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->fill($request->validated());
        if ($request->file('profile_image')) {
            $user->profile_image = $this->save_file($request->file('profile_image'), 'profile_images');
        }
        $user->update();

        return response()->json(new UserResource($user));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function UpdatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6'
        ]);

        $user = User::find(auth('sanctum')->id());

        $user->update(['password' => bcrypt($request->get('password'))]);

        return response()->json(new UserResource($user));
    }

    public function ExportExcel(Request $request)
    {
        return (new UserExport($request->search))->download('users_' . time() . '.xlsx');
    }

    public function ForgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::whereEmail($request->validated('email'))->first();

        if ($user == null) {
            return response()->json([
                'message' => 'Adresse e-mail introuvable',
            ], 400);
        }

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $token = Str::random(8);
        $user->notify(new ApiPasswordResetNotification($token));

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'E-mail envoyé avec succès',
        ]);
    }

    public function CheckResetPasswordToken(Request $request)
    {
        return response()->json([
            'exists' => DB::table('password_reset_tokens')->where('token', $request->token)->exists(),
        ]);
    }

    public function ResetPassword(ResetPasswordRequest $request)
    {
        $password_reset = DB::table('password_reset_tokens')->where('token', $request->token)->first();

        if (!$password_reset) {
            return response()->json([
                'message' => 'Invalid token',
            ], 400);
        }

        $user = User::whereEmail($password_reset->email)->first();

        $user->update([
            'password' => bcrypt($request->validated('password'))
        ]);

        DB::table('password_reset_tokens')->where('token', $request->token)->delete();

        return response()->noContent(200);
    }

    public function UpdateProfile(UpdateProfileRequest $request)
    {
        $user = User::find(auth('sanctum')->id());

        $user->update($request->validated());

        return response()->json(new UserResource($user));
    }

    public function UpdateProfileImage(UpdateProfileImageRequest $request)
    {
        // $user = User::find(auth('sanctum')->id());
        $user = User::find($request->input('id'));
        $user->update([
            'profile_image' => $request->hasFile('avatar') ? $this->save_file($request->file('avatar'), 'profile_images') : null
        ]);

        return response()->json(new UserResource($user));
    }
}
