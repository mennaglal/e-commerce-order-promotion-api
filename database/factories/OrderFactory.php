<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('01#########'),

            'promotion_id' => null,
            'promotion_code' => null,

            'subtotal' => '500.00',
            'discount_amount' => '0.00',
            'total' => '500.00',

            'currency' => Order::CURRENCY,
            'address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),

            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_PENDING,

            'status' => Order::STATUS_PENDING,
        ];
    }

    public function cashOnDelivery(): static
    {
        return $this->state(fn () => [
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_PENDING,
        ]);
    }

    public function onlinePaid(): static
    {
        return $this->state(fn () => [
            'payment_method' => Order::PAYMENT_METHOD_ONLINE,
            'payment_status' => Order::PAYMENT_PAID,
            'paid_at' => now(),
        ]);
    }

    public function onlinePending(): static
    {
        return $this->state(fn () => [
            'payment_method' => Order::PAYMENT_METHOD_ONLINE,
            'payment_status' => Order::PAYMENT_PENDING,
            'paid_at' => null,
        ]);
    }

    public function onlineFailed(): static
    {
        return $this->state(fn () => [
            'payment_method' => Order::PAYMENT_METHOD_ONLINE,
            'payment_status' => Order::PAYMENT_FAILED,
            'paid_at' => null,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
        ]);
    }
}
