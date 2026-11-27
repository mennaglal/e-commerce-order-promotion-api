<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function getUserOrders(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Order::where('user_id', $user->id)
            ->with('items')
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }


    public function cancelOrder(Order $order, ?string $reason = null): array
    {
        return DB::transaction(function () use ($order, $reason) {
            // Lock the order to prevent concurrent cancellation requests
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Already cancelled: return without restoring stock again
            if ($order->status === Order::STATUS_CANCELLED) {
                return ['success' => true, 'order' => $order->load('items')];
            }

            if (! $order->canTransitionTo(Order::STATUS_CANCELLED)) {
                return [
                    'success' => false,
                    'message' => "An order with status '{$order->status}' cannot be cancelled.",
                    'errors'  => ['code' => 'invalid_order_status', 'status' => $order->status],
                ];
            }

            // Restore stock in a fixed order to reduce deadlocks
            foreach ($order->items()->orderBy('product_id')->get() as $item) {
                Product::whereKey($item->product_id)
                    ->increment('stock_quantity', $item->product_quantity);
            }

            // Remove promotion usage
            $order->promotionUsages()->delete();

            $order->status              = Order::STATUS_CANCELLED;
            $order->cancelled_at        = now();
            $order->cancellation_reason = $reason;
            $order->save();

            return ['success' => true, 'order' => $order->load('items')];
        });
    }

}
