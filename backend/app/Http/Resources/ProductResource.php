<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'unit' => $this->unit,
            'cost_price' => $this->cost_price,
            'sell_price' => $this->sell_price,
            'reorder_level' => $this->reorder_level,
            // relative path on the "public" disk; the frontend builds the full URL
            'image' => $this->image,
            'is_active' => $this->is_active,
            'stock_total' => (int) ($this->stock_total ?? 0),
            'suppliers_count' => $this->whenCounted('suppliers'),
            'created_at' => $this->created_at,
        ];
    }
}
