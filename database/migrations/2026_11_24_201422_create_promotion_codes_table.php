<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotion_codes', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->enum('type', ['percentage', 'fixed']);

            $table->decimal('value', 12, 2);

            $table->decimal('min_cart_amount', 12, 2)->nullable();

            $table->decimal('max_discount_amount', 12, 2)->nullable();

            $table->dateTime('starts_at')->nullable();

            $table->dateTime('ends_at')->nullable();

            $table->unsignedInteger('usage_limit')->nullable();

            $table->unsignedInteger('per_customer_limit')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_codes');
    }
};
