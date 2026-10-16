<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            'quantity' => $this->quantity,
            'quantity_received' => $this->quantity_received,
            'quantity_remaining' => max(0, $this->quantity - $this->quantity_received),
            'fully_received' => $this->quantity_received >= $this->quantity,
            'unit_cost' => $this->unit_cost,
            'line_total' => round($this->quantity * (float) $this->unit_cost, 2),
        ];
    }
}
