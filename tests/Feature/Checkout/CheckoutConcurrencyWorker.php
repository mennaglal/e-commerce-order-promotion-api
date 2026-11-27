<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Console\Command;

class CheckoutConcurrencyWorker extends Command
{
    protected $signature = 'checkout:concurrency-worker
        {user_id}
        {idempotency_key}
        {barrier}';

    protected $description = 'Run checkout in a separate process for concurrency testing';


    public function test_users_can_purchase_only_available_stock(): void
    {
        $users = User::factory()->count(3)->create();

        $product = Product::factory()->create([
            'stock_quantity' => 2,
            'status' => 1,
            'price' => '100.00',
        ]);

        foreach ($users as $user) {
            $cart = Cart::create([
                'user_id' => $user->id,
            ]);

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        // User A purchases one item.
        $responseA = $this->actingAs($users[0], 'sanctum')
            ->postJson('/api/checkout', [
                'address' => 'Cairo, Egypt',
                'payment_method' => 'cash_on_delivery',
            ]);

        $responseA->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 1,
        ]);

        // User B purchases the remaining item.
        $responseB = $this->actingAs($users[1], 'sanctum')
            ->postJson('/api/checkout', [
                'address' => 'Cairo, Egypt',
                'payment_method' => 'cash_on_delivery',
            ]);

        $responseB->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 0,
        ]);

        // User C cannot purchase because stock is unavailable.
        $responseC = $this->actingAs($users[2], 'sanctum')
            ->postJson('/api/checkout', [
                'address' => 'Cairo, Egypt',
                'payment_method' => 'cash_on_delivery',
            ]);

        $responseC->assertUnprocessable();

        $this->assertDatabaseCount('orders', 2);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 0,
        ]);
    }
}
