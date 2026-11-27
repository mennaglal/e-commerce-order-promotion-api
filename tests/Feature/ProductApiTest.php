<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use an isolated in-memory cache for testing.
        config(['cache.default' => 'array']);

        Cache::flush();
    }

    /*
    |--------------------------------------------------------------------------
    | Product Factory Helper
    |--------------------------------------------------------------------------
    */

    private function createProduct(
        int $stock = 10,
        string $price = '25.00',
        array $attributes = []
    ): Product {
        return Product::factory()->create(array_merge([
            'name' => fake()->unique()->words(2, true),
            'price' => $price,
            'stock_quantity' => $stock,
            'status' => true,
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Product Listing API
    |--------------------------------------------------------------------------
    */

    public function test_product_listing_returns_active_products_with_pagination(): void
    {
        Product::factory()->count(4)->create([
            'status' => true,
        ]);

        Product::factory()->create([
            'status' => false,
        ]);

        $this->getJson('/api/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_products_can_be_searched_by_name(): void
    {
        $this->createProduct(attributes: [
            'name' => 'Wireless Keyboard',
        ]);

        $this->createProduct(attributes: [
            'name' => 'Gaming Mouse',
        ]);

        $this->getJson('/api/products?search=keyboard')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Wireless Keyboard');
    }

    public function test_search_treats_sql_like_wildcards_as_literal_characters(): void
    {
        $this->createProduct(attributes: [
            'name' => 'Cotton 100% Shirt',
        ]);

        $this->createProduct(attributes: [
            'name' => 'Cotton 100X Shirt',
        ]);

        $this->getJson('/api/products?search=%25')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cotton 100% Shirt');
    }

    public function test_products_can_be_filtered_by_minimum_and_maximum_price(): void
    {
        $this->createProduct(price: '15.00');
        $this->createProduct(price: '75.00');
        $this->createProduct(price: '250.00');

        $this->getJson('/api/products?min_price=50&max_price=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', '75.00');
    }

    public function test_products_can_be_filtered_by_stock_availability(): void
    {
        $this->createProduct(stock: 12);
        $this->createProduct(stock: 0);

        $this->getJson('/api/products?available=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_available', true);

        $this->getJson('/api/products?available=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_available', false);
    }

    public function test_products_can_be_sorted_by_price_ascending(): void
    {
        $this->createProduct(price: '120.00');
        $this->createProduct(price: '35.00');
        $this->createProduct(price: '80.00');

        $this->getJson('/api/products?sort_by=price&sort_direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.price', '35.00')
            ->assertJsonPath('data.1.price', '80.00')
            ->assertJsonPath('data.2.price', '120.00');
    }

    public function test_different_pages_return_different_products(): void
    {
        $this->createProduct();
        $this->createProduct();
        $this->createProduct();

        $firstPage = $this->getJson('/api/products?per_page=1&page=1');

        $secondPage = $this->getJson('/api/products?per_page=1&page=2');

        $firstPage->assertOk()
            ->assertJsonPath('meta.current_page', 1);

        $secondPage->assertOk()
            ->assertJsonPath('meta.current_page', 2);

        $this->assertNotSame(
            $firstPage->json('data.0.id'),
            $secondPage->json('data.0.id')
        );
    }

    public function test_invalid_product_filters_return_validation_errors(): void
    {
        $this->getJson('/api/products?min_price=150&max_price=50')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price'], 'errors');

        $this->getJson('/api/products?sort_by=unknown_column')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort_by'], 'errors');
    }

    /*
    |--------------------------------------------------------------------------
    | Product Details API
    |--------------------------------------------------------------------------
    */

    public function test_product_details_are_returned_successfully(): void
    {
        $product = $this->createProduct(
            stock: 8,
            price: '145.50'
        );

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', $product->sku)
            ->assertJsonPath('data.price', '145.50')
            ->assertJsonPath('data.stock_quantity', 8);
    }

    public function test_inactive_or_nonexistent_products_return_404(): void
    {
        $this->getJson('/api/products/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'not_found')
            ->assertJsonPath('message', 'Product not found.');

        $inactiveProduct = Product::factory()->create([
            'status' => false,
        ]);

        $this->getJson("/api/products/{$inactiveProduct->id}")
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Product Cache Tests
    |--------------------------------------------------------------------------
    */

    public function test_first_request_queries_database_and_second_request_uses_cache(): void
    {
        Product::factory()->count(3)->create([
            'status' => true,
        ]);

        DB::enableQueryLog();

        // First request: Cache Miss
        $firstResult = app(ProductService::class)->getProducts([]);

        $firstQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) =>
            str_contains(strtolower($query['query']), 'products')
            )
            ->count();

        $this->assertGreaterThan(0, $firstQueries);

        // Clear recorded queries.
        DB::flushQueryLog();

        // Second request: Cache Hit
        $secondResult = app(ProductService::class)->getProducts([]);

        $secondQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) =>
            str_contains(strtolower($query['query']), 'products')
            )
            ->count();

        $this->assertSame(
            $firstResult->total(),
            $secondResult->total()
        );

        $this->assertSame(0, $secondQueries);

        DB::disableQueryLog();
    }

    public function test_different_filters_have_independent_cached_results(): void
    {
        $this->createProduct(price: '100.00');
        $this->createProduct(price: '300.00');
        $this->createProduct(price: '600.00');

        $service = app(ProductService::class);

        // Cache all products
        $allProducts = $service->getProducts([]);

        // Cache products with a minimum price.
        $expensiveProducts = $service->getProducts([
            'min_price' => 250,
        ]);

        $this->assertSame(3, $allProducts->total());
        $this->assertSame(2, $expensiveProducts->total());

        DB::enableQueryLog();

        // Both requests should now be served from separate cache entries.
        $cachedAll = $service->getProducts([]);

        $cachedExpensive = $service->getProducts([
            'min_price' => 250,
        ]);

        $this->assertSame(3, $cachedAll->total());
        $this->assertSame(2, $cachedExpensive->total());

        $productQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) =>
            str_contains(strtolower($query['query']), 'products')
            )
            ->count();

        $this->assertSame(0, $productQueries);

        DB::disableQueryLog();
    }

    public function test_product_list_cache_is_invalidated_after_version_changes(): void
    {
        $this->createProduct();

        // First request caches one product.
        $this->assertSame(
            1,
            $this->getJson('/api/products')->json('meta.total')
        );

        // Add another product without invalidating the cache.
        $this->createProduct();

        // Old cached result should still be returned.
        $this->assertSame(
            1,
            $this->getJson('/api/products')->json('meta.total')
        );

        // Increment cache version.
        app(ProductService::class)->flushListCache();

        // New cache key should return fresh database results.
        $this->assertSame(
            2,
            $this->getJson('/api/products')->json('meta.total')
        );
    }

    public function test_application_falls_back_to_database_when_cache_is_unavailable(): void
    {
        Product::factory()->count(2)->create([
            'status' => true,
        ]);

        // Simulate cache connection failure.
        Cache::shouldReceive('get')
            ->andThrow(new \RuntimeException('Cache connection failed'));

        Cache::shouldReceive('put')
            ->andThrow(new \RuntimeException('Cache connection failed'));

        Log::shouldReceive('warning')
            ->atLeast()
            ->once();

        // ProductService should still return database results.
        $result = app(ProductService::class)->getProducts([]);

        $this->assertSame(2, $result->total());
    }
}
