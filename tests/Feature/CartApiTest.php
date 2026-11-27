<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Product Factory Helper
    |--------------------------------------------------------------------------
    */

    private function createProduct(
        int $stock = 10,
        string $price = '25.00',
        array $attributes = []
    ): Product {
        return Product::factory()->create(array_merge([
            'name' => fake()->unique()->words(2, true),
            'price' => $price,
            'stock_quantity' => $stock,
            'status' => true,
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Cart Helpers
    |--------------------------------------------------------------------------
    */

    private function createCart(User $user): Cart
    {
        return Cart::create([
            'user_id' => $user->id,
        ]);
    }

    private function createCartItem(
        User $user,
        Product $product,
        int $quantity = 1
    ): CartItem {
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        return $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cart Authentication
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_access_cart(): void
    {
        $this->getJson('/api/cart')
            ->assertUnauthorized();

        $this->postJson('/api/cart/items', [
            'product_id' => 1,
            'quantity' => 1,
        ])->assertUnauthorized();
    }

    /*
    |--------------------------------------------------------------------------
    | Cart Listing API
    |--------------------------------------------------------------------------
    */

    public function test_user_can_view_their_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10, price: '20.00');

        $this->createCartItem($user, $product, 2);

        Sanctum::actingAs($user);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items_count', 1)
            ->assertJsonPath('data.total_quantity', 2)
            ->assertJsonPath('data.subtotal', '40.00');
    }

    public function test_user_gets_an_empty_cart_when_no_cart_exists(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.subtotal', '0.00');

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
        ]);
    }

    public function test_viewing_cart_multiple_times_does_not_create_duplicate_carts(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/cart')->assertOk();
        $this->getJson('/api/cart')->assertOk();
        $this->getJson('/api/cart')->assertOk();

        $this->assertDatabaseCount('carts', 1);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_only_view_their_own_cart(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $this->createCartItem($firstUser, $firstProduct, 2);
        $this->createCartItem($secondUser, $secondProduct, 5);

        Sanctum::actingAs($firstUser);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $firstProduct->id)
            ->assertJsonPath('data.items.0.quantity', 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Add Product To Cart API
    |--------------------------------------------------------------------------
    */

    public function test_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(
            stock: 10,
            price: '15.50'
        );

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Product added to cart successfully.')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.items.0.unit_price', '15.50')
            ->assertJsonPath('data.items.0.line_total', '46.50')
            ->assertJsonPath('data.subtotal', '46.50')
            ->assertJsonPath('data.total_quantity', 3);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_adding_same_product_twice_increases_existing_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.quantity', 5)
            ->assertJsonPath('data.total_quantity', 5);

        $this->assertDatabaseCount('cart_items', 1);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_user_cannot_add_quantity_greater_than_available_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 3);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 4,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'insufficient_stock')
            ->assertJsonPath('errors.product_id', $product->id)
            ->assertJsonPath('errors.requested', 4)
            ->assertJsonPath('errors.available', 3);

        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_failed_addition_does_not_change_existing_cart_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 5);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertOk();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 4,
        ])->assertStatus(409);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_user_cannot_add_inactive_product_to_cart(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(attributes: [
            'status' => false,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'product_unavailable')
            ->assertJsonPath('errors.product_id', $product->id);

        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_adding_nonexistent_product_returns_validation_error(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => 999999,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_adding_product_without_product_id_returns_validation_error(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_adding_product_with_invalid_quantity_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => -2,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 'invalid',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Cart Item API
    |--------------------------------------------------------------------------
    */

    public function test_user_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        $item = $this->createCartItem($user, $product, 2);

        Sanctum::actingAs($user);

        $this->patchJson("/api/cart/items/{$item->id}", [
            'quantity' => 5,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart item updated successfully.')
            ->assertJsonPath('data.items.0.quantity', 5)
            ->assertJsonPath('data.total_quantity', 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);
    }

    public function test_user_cannot_update_cart_item_with_quantity_greater_than_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 5);

        $item = $this->createCartItem($user, $product, 2);

        Sanctum::actingAs($user);

        $this->patchJson("/api/cart/items/{$item->id}", [
            'quantity' => 6,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'insufficient_stock')
            ->assertJsonPath('errors.requested', 6)
            ->assertJsonPath('errors.available', 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 2,
        ]);
    }

    public function test_user_cannot_update_cart_item_for_inactive_product(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(attributes: [
            'status' => true,
        ]);

        $item = $this->createCartItem($user, $product, 2);

        $product->update([
            'status' => false,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/cart/items/{$item->id}", [
            'quantity' => 3,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'product_unavailable');

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 2,
        ]);
    }

    public function test_updating_cart_item_with_invalid_quantity_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $item = $this->createCartItem($user, $product, 2);

        Sanctum::actingAs($user);

        $this->patchJson("/api/cart/items/{$item->id}", [
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 2,
        ]);
    }

    public function test_updating_nonexistent_cart_item_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->patchJson('/api/cart/items/999999', [
            'quantity' => 2,
        ])->assertNotFound();
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $product = $this->createProduct();

        $item = $this->createCartItem($secondUser, $product, 2);

        Sanctum::actingAs($firstUser);

        $this->patchJson("/api/cart/items/{$item->id}", [
            'quantity' => 5,
        ])->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 2,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Cart Item API
    |--------------------------------------------------------------------------
    */

    public function test_user_can_remove_cart_item(): void
    {
        $user = User::factory()->create();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $item = $this->createCartItem($user, $firstProduct, 2);
        $this->createCartItem($user, $secondProduct, 3);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/cart/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart item removed successfully.')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $secondProduct->id)
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.total_quantity', 3);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ]);
    }

    public function test_user_can_remove_last_item_and_keep_an_empty_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $item = $this->createCartItem($user, $product);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/cart/items/{$item->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.subtotal', '0.00');

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
        ]);
    }

    public function test_removing_nonexistent_cart_item_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->deleteJson('/api/cart/items/999999')
            ->assertNotFound();
    }

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $product = $this->createProduct();

        $item = $this->createCartItem($secondUser, $product, 2);

        Sanctum::actingAs($firstUser);

        $this->deleteJson("/api/cart/items/{$item->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 2,
        ]);
    }
}
