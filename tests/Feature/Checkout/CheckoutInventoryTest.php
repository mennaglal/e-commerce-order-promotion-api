<?php

namespace Tests\Feature\Checkout;

class CheckoutInventoryTest extends CheckoutTestCase
{
    public function test_checkout_fails_when_product_stock_is_insufficient(): void
    {
        $product = $this->createProduct(stock: 1);

        $this->addToCart($product, 2);

        $result = $this->checkout((string) str()->uuid());

        $this->assertFalse($result['success']);

        $this->assertSame(
            'insufficient_stock',
            $result['errors']['code']
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 1,
        ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_fails_when_product_is_inactive(): void
    {
        $product = $this->createProduct(
            stock: 10,
            attributes: ['status' => false]
        );

        $this->addToCart($product);

        $result = $this->checkout((string) str()->uuid());

        $this->assertFalse($result['success']);

        $this->assertSame(
            'product_unavailable',
            $result['errors']['code']
        );

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_does_not_allow_negative_stock(): void
    {
        $product = $this->createProduct(stock: 2);

        $this->addToCart($product, 3);

        $this->checkout((string) str()->uuid());

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 2,
        ]);
    }
}
