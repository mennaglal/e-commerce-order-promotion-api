<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.pages.products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        // Clear the products list cache after add product.
        $this->productService->flushListCache();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }
    public function edit(Product $product): View
    {
        return view('admin.pages.products.edit', compact('product'));
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): RedirectResponse {
        $product->update($request->validated());

        // Clear the products list cache after update product.
        $this->productService->flushListCache();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }
    public function destroy(Product $product): RedirectResponse
    {
        if (
            $product->cartItems()->exists() ||
            $product->orderItems()->exists()
        ) {
            return redirect()
                ->route('admin.products.index')
                ->with(
                    'error',
                    'This product is linked to cart or order items. Deactivate it instead of deleting it.'
                );
        }

        $product->delete();

        // Clear the products list cache after delete product.
        $this->productService->flushListCache();
        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
