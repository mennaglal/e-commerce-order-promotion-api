<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'stock_quantity',
        'status',
    ];

    /*
   |--------------------------------------------------------------------------
   | Casts
   |--------------------------------------------------------------------------
   */
    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'status'         => 'boolean',
    ];

    /*
   |--------------------------------------------------------------------------
   | Relationships
   |--------------------------------------------------------------------------
   */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /*
   |--------------------------------------------------------------------------
   |  Helper Functions
   |--------------------------------------------------------------------------
   */
    public function isActive(): bool
    {
        return $this->status;
    }

    public function hasStockFor(int $quantity): bool
    {
        return $this->stock_quantity >= $quantity;
    }
}
