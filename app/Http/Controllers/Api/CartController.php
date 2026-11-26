<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\CartItemRequest;
use App\Http\Resources\CartResource;
use App\Http\Responses\ApiResponse;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function index(): JsonResponse
    {
        $cart = $this->cartService->getUserCart(request()->user());

        return ApiResponse::success(new CartResource($cart));
    }

    public function store(CartItemRequest $request): JsonResponse
    {
        $result = $this->cartService->addProductToCart(
            $request->user(),
            $request->validated('product_id'),
            $request->validated('quantity')
        );

        if (!$result['available']) {
            return ApiResponse::error(
                $result['message'],
                422,
                $result['errors']
            );
        }

        return ApiResponse::success(
            new CartResource($result['cart']),
            'Product added to cart successfully.'
        );
    }

    public function update(CartItemRequest $request, int $itemId): JsonResponse
    {
        $result = $this->cartService->updateCartItemQuantity(
            $request->user(),
            $itemId,
            $request->validated('quantity')
        );

        if (! $result['available']) {
            return ApiResponse::error(
                $result['message'],
                422,
                $result['errors']
            );
        }

        return ApiResponse::success(
            new CartResource($result['cart']),
            'Cart item updated successfully.'
        );
    }

    public function destroy(int $itemId): JsonResponse
    {
        $cart = $this->cartService->removeCartItem(
            request()->user(),
            $itemId
        );

        return ApiResponse::success(
            new CartResource($cart),
            'Cart item removed successfully.'
        );
    }
}
