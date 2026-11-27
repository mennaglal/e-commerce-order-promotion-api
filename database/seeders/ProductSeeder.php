<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {

        Product::factory(100)->create();
        Product::factory(10)->lowStock()->create();
        Product::factory(10)->outOfStock()->create();
        Product::factory(5)->inactive()->create();
    }
}
