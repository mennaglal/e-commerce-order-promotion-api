<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => ucwords(fake()->words(3, true)),
            'sku'            => strtoupper(fake()->unique()->bothify('SKU-????-####')),
            'description'    => fake()->paragraph(),
            'price'          => fake()->randomFloat(2, 10, 600),
            'stock_quantity' => fake()->numberBetween(1, 100),
            'status'         => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock_quantity' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['stock_quantity' => fake()->numberBetween(1, 5)]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }
}
