<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    private const CURRENCY = 'EGP';

    public function __construct(
         InventoryService $inventory,
         PromotionCodeService $promotions,
    ) {
    }


    public function checkout(User $user, array $data, string $key): array
    {
        DB::beginTransaction();

        try {
            $result = $this->placeOrder($user, $data, $key);

            if (isset($result['error'])) {
                DB::rollBack();

                return $result;
            }

            DB::commit();

            return [
                'order' => $result['order']->load('items'),
                'created' => $result['created'],
            ];

        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();

            $order = $this->findByKey($user, $key);

            if (! $order) {
                throw $e;
            }

            return [
                'order' => $order->load('items'),
                'created' => false,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
    private function placeOrder(User $user, array $data, string $key): array
    {
        $cart = Cart::where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($existing = $this->findByKey($user, $key, lock: true)) {
            return [
                'order' => $existing,
                'created' => false,
            ];
        }

        $items = $cart ? $cart->items()->get() : collect();

        if ($items->isEmpty()) {
            return $this->failure('Your cart is empty.', 'cart_empty');
        }

        $products = Product::whereIn('id', $items->pluck('product_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $subtotal = '0.00';
        $lines = [];

        foreach ($items as $item) {
            $product = $products->get($item->product_id);

            if (! $product || $product->trashed()) {
                return $this->failure(
                    'One or more products are unavailable.',
                    'product_unavailable'
                );
            }

            $inventoryError = $this->inventory->assertAvailable(
                $product,
                $item->quantity
            );

            if ($inventoryError !== null) {
                return ['error' => $inventoryError];
            }

            $subtotal = bcadd(
                $subtotal,
                bcmul($product->price, (string) $item->quantity, 2),
                2
            );

            $lines[] = [$product, $item->quantity];
        }

        $promotion = null;
        $discount = '0.00';

        if (filled($data['promotion_code'] ?? null)) {
            $promotion = $this->promotions->findByCode(
                $data['promotion_code'],
                lock: true
            );

            if (! $promotion) {
                return $this->failure(
                    'Invalid coupon code.',
                    'coupon_invalid'
                );
            }

            $promotionError = $this->promotions->assertEligible(
                $promotion,
                $user,
                $subtotal
            );

            if ($promotionError !== null) {
                return ['error' => $promotionError];
            }

            $discount = $this->promotions->calculateDiscount(
                $promotion,
                $subtotal
            );
        }

        $total = bcsub($subtotal, $discount, 2);

        $order = new Order(
            Arr::only($data, [
                'customer_name',
                'customer_phone',
                'address',
                'notes',
            ])
        );

        $order->user_id = $user->id;
        $order->customer_name ??= $user->name;
        $order->customer_email = $user->email;
        $order->promotion_id = $promotion?->id;
        $order->promotion_code = $promotion?->code;
        $order->subtotal = $subtotal;
        $order->discount_amount = $discount;
        $order->total = $total;
        $order->currency = self::CURRENCY;
        $order->status = Order::STATUS_PENDING;
        $order->idempotency_key = $key;
        $order->save();

        foreach ($lines as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_price' => $product->price,
                'product_quantity' => $quantity,
            ]);

            $updated = Product::whereKey($product->id)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity);

            if ($updated === 0) {
                return $this->failure(
                    "Insufficient stock for '{$product->name}'.",
                    'insufficient_stock',
                    ['product_id' => $product->id]
                );
            }
        }

        if ($promotion) {
            $order->promotionUsages()->create([
                'promotion_id' => $promotion->id,
                'user_id' => $user->id,
            ]);
        }

        $cart->items()->delete();

        return [
            'order' => $order,
            'created' => true,
        ];
    }
    private function findByKey(User $user, string $key, bool $lock = false): ?Order
    {
        $query = Order::where('user_id', $user->id)->where('idempotency_key', $key);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
    private function failure(
        string $message,
        string $code,
        array $extra = []
    ): array {
        return [
            'error' => array_merge([
                'message' => $message,
                'code' => $code,
            ], $extra),
        ];
    }
}
