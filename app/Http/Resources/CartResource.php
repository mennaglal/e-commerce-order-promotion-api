<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'items_count'    => $this->whenLoaded('items', fn () => $this->items->count()),
            'total_quantity' => $this->whenLoaded('items', fn () => (int) $this->items->sum('quantity')),
            'subtotal'       => $this->whenLoaded('items', fn () => $this->subtotal),
            'currency'       => Order::CURRENCY,
        ];
    }
}
