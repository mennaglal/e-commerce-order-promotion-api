<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\User;

class CheckoutIdempotencyTest extends CheckoutTestCase
{
    public function test_same_idempotency_key_returns_the_existing_order(): void
    {
        $product = $this->createProduct(stock: 10);

        $this->addToCart($product, 2);

        $key = (string) str()->uuid();

        $first = $this->checkout($key);

        $this->assertTrue($first['success']);
        $this->assertTrue($first['order']->wasRecentlyCreated);

        $second = $this->checkout($key);

        $this->assertTrue($second['success']);
        $this->assertFalse($second['order']->wasRecentlyCreated);

        $this->assertSame($first['order']->id, $second['order']->id);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);
    }

    public function test_different_idempotency_keys_create_different_orders(): void
    {
        $product = $this->createProduct(stock: 10);

        $this->addToCart($product);

        $first = $this->checkout((string) str()->uuid());

        $this->assertTrue($first['success']);

        // Add the product to the cart again.
        $this->addToCart($product);

        $second = $this->checkout((string) str()->uuid());

        $this->assertTrue($second['success']);

        $this->assertNotSame($first['order']->id, $second['order']->id);

        $this->assertDatabaseCount('orders', 2);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);
    }

}
