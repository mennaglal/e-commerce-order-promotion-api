<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 20);
        return ApiResponse::paginated(
            $this->orderService->getUserOrders($request->user(), $perPage),
            OrderResource::class
        );
    }

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return ApiResponse::success(new OrderResource($order->load('items')));
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('cancel', $order);

        $result = $this->orderService->cancelOrder($order, $request->validated('reason'));

        if (! $result['success']) {
            return ApiResponse::error($result['message'], 422, $result['errors']);
        }

        return ApiResponse::success(new OrderResource($result['order']), 'Order cancelled successfully.');
    }
}
