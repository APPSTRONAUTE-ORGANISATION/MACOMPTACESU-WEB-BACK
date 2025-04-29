<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'admin@mail.com',
            'password' => bcrypt('123456'),
            'country' => '--',
            'phone' => '--',
            'job' => '--',
        ])->assignRole('admin');

        DB::table('subscriptions')->insert([
            'user_id' => $user->id,
            'type' => 'test',
            'stripe_id' => 'admin',
            'stripe_status' => 'active',
            'stripe_price' => 'admin',
        ]);
    }
}
