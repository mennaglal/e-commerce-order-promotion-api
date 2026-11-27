<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductService
{
    private const VERSION_KEY = 'products:list:version';
    private const TTL_KEY = 3600;

    public function getProducts(array $filters): LengthAwarePaginator
    {
        // Normalize filters so the same request always produces the same key
        $filters = array_filter($filters, fn ($value) => $value !== null);
        ksort($filters);

        $key = $this->listKey($filters);

        $cached = $this->safely(fn () => Cache::get($key));

        if ($cached instanceof LengthAwarePaginator) {
            return $cached;
        }

        $products = $this->queryProducts($filters);

        $this->safely(fn () => Cache::put($key, $products, self::TTL_KEY));

        return $products;
    }


    public function flushListCache(): void
    {
        $this->safely(function () {
            Cache::add(self::VERSION_KEY, 0);
            Cache::increment(self::VERSION_KEY);
        });
    }

    public function getProduct(int $id): Product
    {
        return Product::query()
            ->where('status', true)
            ->findOrFail($id);
    }

    private function listKey(array $filters): string
    {
        $version = (int) ($this->safely(fn () => Cache::get(self::VERSION_KEY, 0)) ?? 0);
        $page    = (int) ($filters['page'] ?? 1);

        return "products:list:v{$version}:" . md5(json_encode($filters) . "|page={$page}");
    }

    private function queryProducts(array $filters): LengthAwarePaginator
    {
        $search    = $filters['search'] ?? null;
        $minPrice  = $filters['min_price'] ?? null;
        $maxPrice  = $filters['max_price'] ?? null;
        $available = $filters['available'] ?? null;

        $sort      = $filters['sort_by'] ?? 'created_at';
        $direction = $filters['sort_direction'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 20;

        return Product::query()
            ->where('status', true)

            ->when(
                filled($search),
                function ($query) use ($search) {
                    $escapedSearch = str_replace(
                        ['!', '%', '_'],
                        ['!!', '!%', '!_'],
                        $search
                    );

                    $query->whereRaw(
                        "name LIKE ? ESCAPE '!'",
                        ['%' . $escapedSearch . '%']
                    );
                }
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

            // "id" as a second sort key
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString();
    }


    private function safely(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::warning('Product list cache is unavailable, falling back to the database.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
