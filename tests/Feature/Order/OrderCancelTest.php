<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_pending_order_and_restore_stock(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'status' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_price' => $product->price,
            'product_quantity' => 2,
        ]);

        $product->decrement('stock_quantity', 2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Customer changed their mind.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED,
            'cancellation_reason' => 'Customer changed their mind.',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);
    }

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PENDING,
        ]);
    }

    public function test_customer_cannot_cancel_an_order_that_is_not_cancellable(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(409)
            ->assertJsonPath('code', 'invalid_order_status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);
    }

    public function test_cancelling_an_already_cancelled_order_does_not_restore_stock_twice(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'status' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CANCELLED,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_price' => $product->price,
            'product_quantity' => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED,
        ]);
    }

    public function test_guest_cannot_cancel_an_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->postJson("/api/orders/{$order->id}/cancel")
            ->assertUnauthorized();
    }
}
