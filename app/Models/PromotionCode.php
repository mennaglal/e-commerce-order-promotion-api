<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_cart_amount',
        'max_discount_amount',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_customer_limit',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_cart_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class, 'promotion_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'promotion_id');
    }
}
