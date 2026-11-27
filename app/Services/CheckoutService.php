<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    private const CURRENCY = 'EGP';

    public function __construct(
        private InventoryService $inventory,
        private PromotionCodeService $promotions,
    ) {
    }

    public function checkout(User $user, array $data, string $idempotency_key): array
    {
        try {
            $result = DB::transaction(
                fn () => $this->placeOrder($user, $data, $idempotency_key)
            );

            // Dispatch the event only for newly created orders.
            if ($result['success'] && $result['order']->wasRecentlyCreated) {
                try {
                    OrderPlaced::dispatch($result['order']);
                } catch (\Throwable $e) {
                    Log::error('Failed to dispatch OrderPlaced event.', [
                        'order_id' => $result['order']->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $result;
        }
        catch (UniqueConstraintViolationException $e) {

            // Two requests used the same key at the same time,
            // so the database rejected the second one.

            $order = $this->findByKey($user, $idempotency_key) ?? throw $e;

            return ['success' => true, 'order' => $order->load('items')];
        }
    }


    private function placeOrder(User $user, array $data, string $idempotency_key): array
    {
        // Lock the user cart to prevent concurrent checkout requests.
        $cart = Cart::where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        // Check if an order with this key already exists.
        $existing = $this->findByKey($user, $idempotency_key);
        if ($existing) {
            return ['success' => true, 'order' => $existing->load('items')];
        }

        // Make sure the cart is not empty.
        $items = $cart ? $cart->items()->get() : collect();

        if ($items->isEmpty()) {
            return $this->failure('Your cart is empty.', 'cart_empty');
        }

        // Lock products in ID order to reduce the risk of deadlocks.
        $products = Product::whereIn('id', $items->pluck('product_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Check stock and calculate subtotal using database prices.
        $subtotal = '0.00';
        $lines = [];

        foreach ($items as $item) {
            $product = $products[$item->product_id];

            $check = $this->inventory->checkAvailability($product, $item->quantity);

            if (! $check['available']) {
                return $this->failure(
                    $check['message'],
                    $check['errors']['code'],
                    $check['errors']
                );
            }

            $subtotal = bcadd(
                $subtotal,
                bcmul($product->price, (string) $item->quantity, 2),
                2
            );

            $lines[] = [
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_price' => $product->price,
                'product_quantity' => $item->quantity,
            ];
        }

        // Validate the promotion and calculate the discount.
        $promotion = null;
        $discount = '0.00';

        if (filled($data['promotion_code'] ?? null)) {
            $promotion = $this->promotions->findByCode(
                $data['promotion_code'],
                true
            );

            if (! $promotion) {
                return $this->failure('Invalid coupon code.', 'coupon_invalid');
            }

            $eligibility = $this->promotions->checkEligibility(
                $promotion,
                $user,
                $subtotal
            );

            if (! $eligibility['valid']) {
                return $this->failure(
                    $eligibility['message'],
                    $eligibility['errors']['code']
                );
            }

            $discount = $this->promotions->calculateDiscount($promotion, $subtotal);
        }

        // All checks passed. Start creating the order.

        $order = new Order([
            'customer_name'  => $data['customer_name'] ?? $user->name,
            'customer_email' => $data['customer_email'] ?? $user->email,
            'customer_phone' => $data['customer_phone'] ?? $user->phone,
            'address'        => $data['address'],
            'payment_method'        => $data['payment_method'],
            'notes'          => $data['notes'] ?? null,
        ]);

        $order->user_id = $user->id;
        $order->promotion_id = $promotion?->id;
        $order->promotion_code = $promotion?->code;
        $order->subtotal = $subtotal;
        $order->discount_amount = $discount;
        $order->total = bcsub($subtotal, $discount, 2);
        $order->currency = self::CURRENCY;
        $order->status = Order::STATUS_PENDING;
        $order->idempotency_key = $idempotency_key;

        $order->save();

        foreach ($lines as $line) {

            // Save product details to keep the original order information.
            $order->items()->create([
                'product_id'       => $line['product_id'],
                'product_name'     => $line['product_name'],
                'product_sku'      => $line['product_sku'],
                'product_price'    => $line['product_price'],
                'product_quantity' => $line['product_quantity'],
            ]);

            // Reduce stock after checking availability.
            $line['product']->decrement(
                'stock_quantity',
                $line['product_quantity']
            );
        }

        if ($promotion) {
            $order->promotionUsages()->create([
                'promotion_id' => $promotion->id,
                'user_id'      => $user->id,
            ]);
        }

        // Clear the cart after successfully creating the order.
        $cart->items()->delete();

        return ['success' => true, 'order' => $order->load('items')];
    }

    private function findByKey(User $user, string $idempotency_key, bool $lock = false): ?Order
    {
        $query = Order::where('user_id', $user->id)->where('idempotency_key', $idempotency_key);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function failure(string $message, string $code, array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors'  => array_merge($errors, ['code' => $code]),
        ];
    }
}
