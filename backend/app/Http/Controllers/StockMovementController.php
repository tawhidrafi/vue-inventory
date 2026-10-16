<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->orderByDesc('id');

        return StockMovementResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StockMovementRequest $request, StockService $service)
    {
        $data = $request->validated();

        $product = Product::findOrFail($data['product_id']);
        $warehouse = Warehouse::findOrFail($data['warehouse_id']);

        // 'in'/'out' carry a magnitude; 'adjustment' keeps its sign.
        $delta = match ($data['type']) {
            'out' => -abs((int) $data['quantity']),
            'adjustment' => (int) $data['quantity'],
            default => abs((int) $data['quantity']),
        };

        $movement = $service->record(
            $product,
            $warehouse,
            $data['type'],
            $delta,
            $data['note'] ?? null,
            null,
            $request->user(),
        );

        return (new StockMovementResource($movement->load(['product', 'warehouse', 'user'])))
            ->response()
            ->setStatusCode(201);
    }
}
