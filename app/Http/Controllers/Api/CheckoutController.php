<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;


class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkoutService)
    {
    }

    public function createOrder(CheckoutRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->checkoutService->checkout(
            $request->user(),
            $data,
            $data['idempotency_key']
        );

        if (! $result['success']) {
            return ApiResponse::failure($result);
        }

        $order = $result['order'];
        $resource = new OrderResource($order);

        // Check if the order was newly created.
        if ($order->wasRecentlyCreated) {
            return ApiResponse::created($resource, 'Order placed successfully.');
        }

        return ApiResponse::success($resource, 'This order was already placed with the same Idempotency-Key.');
    }
}
