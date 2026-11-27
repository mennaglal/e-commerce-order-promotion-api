<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CheckoutTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Cart::create([
            'user_id' => $this->user->id,
        ]);
    }

    protected function createProduct(
        int $stock = 10,
        string $price = '100.00',
        array $attributes = []
    ): Product {
        return Product::factory()->create(array_merge([
            'price' => $price,
            'stock_quantity' => $stock,
            'status' => true,
        ], $attributes));
    }

    protected function addToCart(Product $product, int $quantity = 1): void
    {
        CartItem::create([
            'cart_id' => $this->user->cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    protected function checkoutData(array $attributes = []): array
    {
        return array_merge([
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => $this->user->phone,
            'address' => 'Cairo, Egypt',
            'payment_method' => 'cash_on_delivery',
        ], $attributes);
    }

    protected function checkout(string $key, array $attributes = []): array
    {
        return app(\App\Services\CheckoutService::class)->checkout(
            $this->user,
            $this->checkoutData($attributes),
            $key
        );
    }
}
