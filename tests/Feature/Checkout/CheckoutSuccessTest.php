<?php

namespace Tests\Feature\Checkout;

use App\Models\Order;

class CheckoutSuccessTest extends CheckoutTestCase
{
    public function test_customer_can_checkout_successfully(): void
    {
        $product = $this->createProduct(stock: 10, price: '100.00');

        $this->addToCart($product, 2);

        $result = $this->checkout((string) str()->uuid());

        $this->assertTrue($result['success']);

        $order = $result['order'];

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $this->user->id,
            'subtotal' => '200.00',
            'discount_amount' => '0.00',
            'total' => '200.00',
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_quantity' => 2,
            'product_price' => '100.00',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);

        $this->assertDatabaseCount('cart_items', 0);
    }


    public function test_checkout_calculates_subtotal_from_current_product_prices(): void
    {
        $first = $this->createProduct(price: '100.00');
        $second = $this->createProduct(price: '50.50');

        $this->addToCart($first, 2);
        $this->addToCart($second, 1);

        $result = $this->checkout((string) str()->uuid());

        $this->assertTrue($result['success']);
        $this->assertSame('250.50', $result['order']->subtotal);
        $this->assertSame('250.50', $result['order']->total);
    }
}
