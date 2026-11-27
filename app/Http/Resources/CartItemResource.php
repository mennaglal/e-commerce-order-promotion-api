<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->whenLoaded('product', fn () => $this->product->price),
            'line_total' => $this->whenLoaded('product', fn () => $this->line_total),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
