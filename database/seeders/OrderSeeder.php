<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            // Cash on delivery orders
            Order::factory()
                ->count(2)
                ->for($user)
                ->cashOnDelivery()
                ->create();

            // Successfully paid online orders
            Order::factory()
                ->count(2)
                ->for($user)
                ->onlinePaid()
                ->create();

            // Pending online payment
            Order::factory()
                ->for($user)
                ->onlinePending()
                ->create();

            // Failed online payment
            Order::factory()
                ->for($user)
                ->onlineFailed()
                ->create();

            // Delivered order
            Order::factory()
                ->for($user)
                ->onlinePaid()
                ->status(Order::STATUS_DELIVERED)
                ->create();

            // Cancelled order
            Order::factory()
                ->for($user)
                ->cashOnDelivery()
                ->status(Order::STATUS_CANCELLED)
                ->create();
        }
    }
}
