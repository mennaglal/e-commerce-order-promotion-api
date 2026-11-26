<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(ProductFilterRequest $request): JsonResponse
    {
        $products = $this->productService->getProducts(
            $request->validated()
        );

        return ApiResponse::paginated(
            $products,
            ProductResource::class,
            'Products retrieved successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);

        return ApiResponse::success(
            (new ProductResource($product))->resolve(),
            'Product retrieved successfully.'
        );
    }
}
