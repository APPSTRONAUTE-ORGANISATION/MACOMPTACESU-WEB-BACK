<?php

namespace App\Listeners;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription;

class StripeEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        Log::info('stripe : ' . $event->payload['type']);
        Log::info($event->payload);
        switch ($event->payload['type']) {
            case 'invoice.payment_succeeded':
                $customer_id = $event->payload['data']['object']['customer'];
                $subscription_id = $event->payload['data']['object']['subscription'];
                $price_id = $event->payload['data']['object']['lines']['data'][0]['price']['id'];

                $sub = Subscription::where('stripe_id', $subscription_id)->first();
                $plan = Plan::where('price_id', $price_id)->first();
                $sub->update([
                    'type' => $plan->name,
                    'stripe_status' => 'active',
                ]);

                $u = User::where('stripe_id', $customer_id)->first();
                $method = $u->paymentMethods()->last()->asStripePaymentMethod()->card;

                $u->update([
                    'pm_type' => $method->brand,
                    'pm_last_four' => $method->last4,
                    'exp_month' => $method->exp_month,
                    'exp_year' => $method->exp_year,
                ]);
               
                break;
            case 'customer.subscription.created':
                Log::info('customer.subscription.created');
                // $subscription = $event; // contains a \Stripe\Subscription
                // Then define and call a method to handle the subscription being created.
                // handleSubscriptionCreated($subscription);
                break;
                // case 'charge.succeeded':
                //     $customer_id = $event->payload['data']['object']['customer'];
                //     $payment_method_id = $event->payload['data']['object']['payment_method'];
                //     $payment_method_type = $event->payload['data']['object']['payment_method_details']['type'];

                //     $user = User::where('stripe_id', $customer_id)->first();
                //     if ($user == null) {
                //         break;
                //     }
                //     $user->update([
                //         'payment_method_id' => $payment_method_id
                //     ]);

                //     if ($payment_method_type == 'card') {
                //         $card = $event->payload['data']['object']['payment_method_details']['card'];
                //     }
                //     break;
            default:
                # code...
                break;
        }
    }
}
