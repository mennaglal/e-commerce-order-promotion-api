<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function getUserCart(User $user): Cart
    {
        return $this->findOrCreateUserCart($user)->load('items.product');
    }

    public function addProductToCart(User $user, int $productId, int $quantity): array
    {
        return DB::transaction(function () use ($user, $productId, $quantity) {
            $cart = $this->findOrCreateLockedUserCart($user);
            $product = Product::findOrFail($productId);

            $existingQuantity = $cart->items()
                ->where('product_id', $product->id)
                ->value('quantity') ?? 0;

            $totalQuantity = $existingQuantity + $quantity;

            $inventoryCheck = $this->inventory->checkAvailability($product, $totalQuantity);

            if (! $inventoryCheck['available']) {
                return $inventoryCheck;
            }

            $cart->items()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $totalQuantity]
            );

            return [
                'available' => true,
                'cart' => $cart->load('items.product'),
            ];
        });
    }

    public function updateCartItemQuantity(User $user, int $itemId, int $quantity): array
    {
        return DB::transaction(function () use ($user, $itemId, $quantity) {
            $cart = $this->findOrCreateLockedUserCart($user);
            $item = $cart->items()->with('product')->findOrFail($itemId);

            $inventoryCheck = $this->inventory->checkAvailability($item->product, $quantity);

            if (! $inventoryCheck['available']) {
                return $inventoryCheck;
            }

            $item->update(['quantity' => $quantity]);

            return [
                'available' => true,
                'cart' => $cart->load('items.product'),
            ];
        });
    }

    public function removeCartItem(User $user, int $itemId): Cart
    {
        $cart = $this->findOrCreateUserCart($user);

        $cart->items()->findOrFail($itemId)->delete();

        return $cart->load('items.product');
    }

    private function findOrCreateUserCart(User $user): Cart
    {
        return Cart::createOrFirst(['user_id' => $user->id]);
    }

    private function findOrCreateLockedUserCart(User $user): Cart
    {
        $cart = $this->findOrCreateUserCart($user);

        return Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
    }
}
