<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_their_orders(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_customer_cannot_see_other_customers_orders(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Order::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        Order::factory()->count(3)->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_orders_are_paginated(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_guest_cannot_view_orders(): void
    {
        $this->getJson('/api/orders')
            ->assertUnauthorized();
    }
}
