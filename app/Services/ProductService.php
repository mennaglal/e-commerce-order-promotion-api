<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private const LIST_TTL = 3600;

    private const VERSION_KEY = 'products:list:version';

    public function getProducts(array $filters): LengthAwarePaginator
    {
        // Normalize filters to generate consistent cache keys.
        ksort($filters);

        $version = Cache::get(self::VERSION_KEY, 0);

        $page = request()->integer('page', 1);

        // Generate32. a unique cache key for each filter combination and page.
        $key = "products:list:v{$version}:" .
            md5(json_encode($filters) . "|page={$page}");

        return Cache::remember(
            $key,
            self::LIST_TTL,
            fn () => $this->queryProducts($filters)
        );
    }

    public function flushListCache(): void
    {
        // Incrementing the version invalidates all existing product list cache keys.
        Cache::increment(self::VERSION_KEY);
    }

    private function queryProducts(array $filters): LengthAwarePaginator
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
