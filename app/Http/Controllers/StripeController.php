<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Product;

class StripeController extends Controller
{
    public function CreateClientSecret(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find(auth('sanctum')->id());

        // $s = $user->newSubscription($plan->name, $plan->price_id)->create('pm_1Q6FKqA4k9xO8SedAkDMPHrU');

        $stripe = new StripeClient(env('STRIPE_SECRET'));
        $subscription = $stripe->subscriptions->create([
            'customer' => $user->stripe_id,
            'items' => [[
                'price' => $plan->price_id,
            ]],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'save_default_payment_method' => 'on_subscription',
                'payment_method_types' => ['card']
            ],
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        return response()->json([
            'client_secret' => $subscription->latest_invoice->payment_intent->client_secret
        ]);
    }

    public function Subscriptions(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $user = auth('sanctum')->user();

        $query = DB::table('subscriptions')
            ->join('plans', 'plans.price_id', '=', 'subscriptions.stripe_price')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->where('subscriptions.stripe_status', 'active');

        if ($user->hasRole('client')) {
            $query->where('subscriptions.user_id', $user->id);
        }

        if ($from && $to) {
            $query->whereDate('subscriptions.updated_at', '>=', $from);
            $query->whereDate('subscriptions.updated_at', '<=', $to);
        }

        $query->select([
            'subscriptions.id',
            'users.first_name',
            'users.last_name',
            'subscriptions.type',
            'plans.price',
            'subscriptions.updated_at as created_at',
        ]);

        $data = $query->paginate(10);

        $stripeHistory = [];

        if ($user->stripe_id && $user->stripe_id !== 'admin') {
            Stripe::setApiKey(config('cashier.secret'));

            $stripeSubscriptions = StripeSubscription::all([
                'customer' => $user->stripe_id,
                'limit' => 100,  
            ]);
            

            foreach ($stripeSubscriptions->data as $sub) {
                $statusFr = match ($sub->status) {
                    'active' => 'Active',
                    'incomplete' => 'Incomplète',
                    'incomplete_expired' => 'Incomplète expirée',
                    'trialing' => 'Essai gratuit',
                    'past_due' => 'Paiement en retard',
                    'canceled' => 'Annulée',
                    'unpaid' => 'Impayée',
                    default => ucfirst($sub->status),
                };

                $stripeHistory[] = [
                    'user_name' => $user->first_name . ' ' . $user->last_name,
                    // 'stripe_id' => $sub->id,
                    'status' => $statusFr,
                    'plan' => Product::retrieve($sub->items->data[0]->price->product)->name ?? 'N/A',
                    'price' => number_format($sub->items->data[0]->price->unit_amount / 100, 2),
                    'start_date' => \Carbon\Carbon::createFromTimestamp($sub->start_date)->toDateTimeString(),
                    'current_period_end' => \Carbon\Carbon::createFromTimestamp($sub->current_period_end)->toDateTimeString(),
                    // 'cancel_at_period_end' => $sub->cancel_at_period_end,
                ];
            }
        }

        return response()->json([
            'data' => SubscriptionResource::collection($data),
            'stripe_history' => $stripeHistory,
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
        ]);
    }

    public function getUserCardInfo()
    {
        $user = auth('sanctum')->user();
        if (!$user->stripe_id || $user->stripe_id === 'admin') {
            return null;
        }

        Stripe::setApiKey(config('cashier.secret'));

        $paymentMethods = PaymentMethod::all([
            'customer' => $user->stripe_id,
            'type' => 'card',
        ]);

        $defaultCard = $paymentMethods->data[0]->card ?? null;

        if (!$defaultCard) {
            return null;
        }

        return [
            'card_brand' => ucfirst($defaultCard->brand),
            'card_last4' => $defaultCard->last4,
        ];
    }

    public function CancelSubscriptions(Subscription $subscription)
    {
        if (auth('sanctum')->user()->hasRole('client') && $subscription->user_id != auth('sanctum')->id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $subscription->cancelNow();

        return response()->noContent();
    }

    public function createFreeTrial(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $user = User::findOrFail($request->id);

        $freePlan = \App\Models\Plan::where('name', 'Gratuit')->first();
    
        if (!$freePlan || !$freePlan->price_id) {
            return response()->json(['error' => 'Le plan "Gratuit" n\'est pas configuré.'], 400);
        }
        
        try {

            // Abonnement avec une période d'essai de 45 jours
            $subscription = $stripe->subscriptions->create([
                'customer' => $user->stripe_id,
                // 'items' => [['price' => 'price_1QkcWJ09QJ6k1O1bQ64tGR1H']], // price_id test
                'items' => [['price' => $freePlan->price_id]],
                'trial_end' => now()->addDays(45)->timestamp, 
                'cancel_at' => now()->addDays(45)->timestamp, // Annulation automatique après 45j
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                    'payment_method_types' => ['card']
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            $freeTrial = Subscription::firstOrCreate(
                ['user_id' => $request->id],
                [
                    'type' => "gratuit",
                    'stripe_id' => $subscription->id,
                    'stripe_status' => "active",
                    'stripe_price' => "0",
                    'trial_ends_at' => now()->addDays(45),  // 45 jours après aujourd'hui
                    'created_at' => now(),
                    'trial_started_at' => now(), 
                ]
            );

            $freeTrial->save();

            return response()->json([
                'success' => true,
                'message' => 'Free trial created successfully.',
                'subscription' => $subscription,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the free trial.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
