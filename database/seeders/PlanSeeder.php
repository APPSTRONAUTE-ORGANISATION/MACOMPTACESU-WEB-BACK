<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Cashier\Subscription as CashierSubscription;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Subscription;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $products = Product::all();

        foreach ($products as $key => $value) {
            $price = Price::retrieve($value->default_price);

            Plan::create([
                'name' => $value->name,
                'price' => $price->unit_amount / 100,
                'price_id' => $price->id,
                'features' => json_encode([
                    'NA',
                ])
            ]);
        }
    }
}
