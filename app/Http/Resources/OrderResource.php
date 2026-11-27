<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'status'              => $this->status,

            // Customer Information
            'customer_name'       => $this->customer_name,
            'customer_email'      => $this->customer_email,
            'customer_phone'      => $this->customer_phone,
            'address'             => $this->address,

            'notes'               => $this->notes,

            // Promotion Information
            'promotion_code'      => $this->promotion_code,

            // Financial Information
            'subtotal'            => $this->subtotal,
            'discount_amount'     => $this->discount_amount,
            'total'               => $this->total,
            'currency'            => $this->currency,

            // Payment Information
            'payment_method'      => $this->payment_method,
            'payment_status'      => $this->payment_status,

            // Order Items
            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            // Order Timeline
            'paid_at'             => $this->paid_at?->toISOString(),
            'confirmed_at'        => $this->confirmed_at?->toISOString(),
            'shipped_at'          => $this->shipped_at?->toISOString(),
            'delivered_at'        => $this->delivered_at?->toISOString(),
            'cancelled_at'        => $this->cancelled_at?->toISOString(),

            'cancellation_reason' => $this->cancellation_reason,

            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
