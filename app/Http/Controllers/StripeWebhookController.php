<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        switch ($payload['type']) {
            case 'customer.subscription.trial_will_end':
                $subscriptionId = $payload['data']['object']['id'];
                $user = User::where('stripe_subscription_id', $subscriptionId)->first();

                // if ($user) {
                //     // Envoyer une notification à l'utilisateur 
                //     // Exemple : Mail::to($user->email)->send(new TrialEndingNotification());
                // }
                break;

            case 'invoice.payment_failed':
                $subscriptionId = $payload['data']['object']['subscription'];
                $user = User::where('stripe_subscription_id', $subscriptionId)->first();

                // if ($user) {
                //     // Suspendre l'accès de l'utilisateur ou notifier l'échec de paiement
                // }
                break;

            case 'invoice.payment_succeeded':
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
