<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockTransferRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;

class StockTransferController extends Controller
{
    public function store(StockTransferRequest $request, StockService $service)
    {
        $data = $request->validated();

        $service->transfer(
            Product::findOrFail($data['product_id']),
            Warehouse::findOrFail($data['from_warehouse_id']),
            Warehouse::findOrFail($data['to_warehouse_id']),
            (int) $data['quantity'],
            $data['note'] ?? null,
            $request->user(),
        );

        return response()->json(['message' => 'Stock transferred.'], 201);
    }
}
