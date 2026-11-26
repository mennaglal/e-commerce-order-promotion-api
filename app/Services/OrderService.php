<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Order::where('user_id', $user->id)
            ->with('items')
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            // نقفل الأوردر: طلبين إلغاء متزامنين يمشوا واحد ورا التاني
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // اتلغى قبل كده؟ نرجّعه من غير ما نرجّع المخزون تاني
            if ($order->status === Order::STATUS_CANCELLED) {
                return $order->load('items');
            }

            if (! $order->canTransitionTo(Order::STATUS_CANCELLED)) {
                throw new BusinessException(
                    "An order with status '{$order->status}' cannot be cancelled.",
                    422,
                    ['code' => 'invalid_order_status', 'status' => $order->status]
                );
            }

            // رجّع المخزون (بترتيب ثابت عشان الـ deadlock)
            foreach ($order->items()->orderBy('product_id')->get() as $item) {
                Product::withTrashed()
                    ->whereKey($item->product_id)
                    ->increment('stock_quantity', $item->product_quantity);
            }

            // رجّع الكوبون للعميل
            $order->promotionUsages()->delete();

            $order->status              = Order::STATUS_CANCELLED;
            $order->cancelled_at        = now();
            $order->cancellation_reason = $reason;
            $order->save();

            return $order->load('items');
        });
    }
}
