<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;

class CheckoutStockTest extends CheckoutTestCase
{
    public function test_stock_never_goes_negative_when_customers_compete(): void
    {
        $product = $this->createProduct(5);

        $userA = $this->user;

        $userB = User::factory()->create();

        Cart::create([
            'user_id' => $userB->id,
        ]);

        CartItem::create([
            'cart_id' => $userA->cart->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        CartItem::create([
            'cart_id' => $userB->cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // First customer purchases 4 items.
        $resultA = $this->checkout(
            '11111111-1111-4111-8111-111111111111'
        );

        $this->assertTrue($resultA['success']);

        $this->assertSame(
            1,
            $product->fresh()->stock_quantity
        );

        // Second customer attempts to purchase 3 items.
        $resultB = app(\App\Services\CheckoutService::class)->checkout(
            $userB,
            [
                'address' => 'Cairo, Egypt',
                'payment_method' => 'cash_on_delivery',
            ],
            '22222222-2222-4222-8222-222222222222'
        );

        $this->assertFalse($resultB['success']);

        $this->assertSame(
            'insufficient_stock',
            $resultB['errors']['code']
        );

        $this->assertSame(
            1,
            $resultB['errors']['available']
        );

        $this->assertSame(
            1,
            $product->fresh()->stock_quantity
        );

        $this->assertDatabaseCount('orders', 1);

        $this->assertSame(
            $userA->id,
            Order::first()->user_id
        );
    }
}
