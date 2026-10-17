<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_order_id' => $this->sales_order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            'product_unit' => $this->whenLoaded('product', fn () => $this->product->unit),
            'quantity' => $this->quantity,
            'quantity_shipped' => $this->quantity_shipped,
            'quantity_remaining' => max(0, $this->quantity - $this->quantity_shipped),
            'fully_shipped' => $this->quantity_shipped >= $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total ?: round($this->quantity * (float) $this->unit_price, 2),
        ];
    }
}
