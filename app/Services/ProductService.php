<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{


    public function getProducts(array $filters): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $available = $filters['available'] ?? null;

        $sort = $filters['sort_by'] ?? 'created_at';
        $direction = $filters['sort_direction'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 20;

        return Product::query()
            ->where('status', true)

            ->when(
                filled($search),
                fn ($q) => $q->where(
                    'name',
                    'like',
                    '%' . addcslashes($search, '%_\\') . '%'
                )
            )

            ->when(
                isset($minPrice),
                fn ($q) => $q->where('price', '>=', $minPrice)
            )

            ->when(
                isset($maxPrice),
                fn ($q) => $q->where('price', '<=', $maxPrice)
            )

            ->when(
                isset($available),
                fn ($q) => $q->where(
                    'stock_quantity',
                    filter_var($available, FILTER_VALIDATE_BOOLEAN) ? '>' : '=',
                    0
                )
            )

            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getProduct(int $id): Product
    {
        return Product::query()
            ->where('status', true)
            ->findOrFail($id);
    }
}
