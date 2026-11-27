<?php

namespace Database\Factories;

use App\Models\PromotionCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionCode>
 */
class PromotionCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE-????-##')),
            'type' => fake()->randomElement([
                PromotionCode::TYPE_FIXED,
                PromotionCode::TYPE_PERCENTAGE,
            ]),
            'value' => fake()->randomFloat(2, 5, 30),
            'min_cart_amount' => fake()->randomElement([null, 50, 100, 150]),
            'max_discount_amount' => null,
            'usage_limit' => null,
            'per_customer_limit' => null,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn () => [
            'type' => PromotionCode::TYPE_PERCENTAGE,
            'value' => fake()->numberBetween(5, 50),
            'max_discount_amount' => fake()->randomElement([30, 50, 100]),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn () => [
            'type' => PromotionCode::TYPE_FIXED,
            'value' => fake()->randomFloat(2, 5, 100),
            'max_discount_amount' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'ends_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function limitedUsage(): static
    {
        return $this->state(fn () => [
            'usage_limit' => fake()->numberBetween(1, 10),
            'per_customer_limit' => fake()->numberBetween(1, 3),
        ]);
    }
}
