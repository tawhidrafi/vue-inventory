<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => $this->quantity,
            'product' => new ProductResource($this->whenLoaded('product')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'updated_at' => $this->updated_at,
        ];
    }
}
