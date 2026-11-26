<?php

namespace App\Services;

use App\Models\Product;

class InventoryService
{
    public function checkAvailability(Product $product, int $requestedQuantity): array
    {
        if (!$product->isActive()) {
            return [
                'available' => false,
                'message' => "Product '{$product->name}' is no longer available.",
                'errors' => [
                    'code' => 'product_unavailable',
                    'product_id' => $product->id,
                ],
            ];
        }

        if (!$product->hasStockFor($requestedQuantity)) {
            return [
                'available' => false,
                'message' => "Insufficient stock for '{$product->name}'.",
                'errors' => [
                    'code' => 'insufficient_stock',
                    'product_id' => $product->id,
                    'requested' => $requestedQuantity,
                    'available' => $product->stock_quantity,
                ],
            ];
        }

        return ['available' => true];
    }
}
