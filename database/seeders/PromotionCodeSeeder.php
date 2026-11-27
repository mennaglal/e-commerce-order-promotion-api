<?php

namespace Database\Seeders;

use App\Models\PromotionCode;
use Illuminate\Database\Seeder;

class PromotionCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromotionCode::factory(10)->percentage()->create();

        PromotionCode::factory(10)->fixed()->create();

        PromotionCode::factory(5)->expired()->create();

        PromotionCode::factory(5)->scheduled()->create();

        PromotionCode::factory(5)->inactive()->create();

        PromotionCode::factory(5)->limitedUsage()->create();
    }
}
