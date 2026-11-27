<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionUsage extends Model
{
    protected $fillable = [
        'promotion_id',
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(PromotionCode::class, 'promotion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
