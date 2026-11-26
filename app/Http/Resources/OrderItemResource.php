<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'name'       => $this->product_name,
            'sku'        => $this->product_sku,
            'unit_price' => $this->product_price,
            'quantity'   => $this->product_quantity,
            'line_total' => $this->line_total,
        ];
    }
}
