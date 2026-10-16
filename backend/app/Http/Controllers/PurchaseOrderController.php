<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query()
            ->with('supplier')
            ->withCount('items')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->search, fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->orderByDesc('id');

        return PurchaseOrderResource::collection(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(PurchaseOrderRequest $request)
    {
        $data = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($data, $request) {
            // create with a temporary number, then replace it with the sequential one
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'number' => 'TMP-'.Str::upper(Str::random(20)),
                'status' => 'draft',
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
                'total' => 0,
            ]);

            $purchaseOrder->update([
                'number' => 'PO-'.str_pad((string) $purchaseOrder->id, 5, '0', STR_PAD_LEFT),
            ]);

            $this->syncItems($purchaseOrder, $data['items']);

            return $purchaseOrder;
        });

        return (new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.product'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.product']));
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be edited.'], 422);
        }

        $data = $request->validated();

        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($purchaseOrder, $data['items']);
        });

        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.product']));
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be deleted.'], 422);
        }

        $purchaseOrder->delete();

        return response()->noContent();
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, StockService $stock)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be received.'], 422);
        }

        $warehouse = Warehouse::findOrFail($request->validated()['warehouse_id']);
        $updateCost = $request->boolean('update_cost');

        DB::transaction(function () use ($purchaseOrder, $warehouse, $stock, $request, $updateCost) {
            $purchaseOrder->load('items');

            foreach ($purchaseOrder->items as $item) {
                $product = Product::withTrashed()->find($item->product_id);

                $stock->record(
                    $product,
                    $warehouse,
                    'in',
                    $item->quantity,
                    "Received on {$purchaseOrder->number}",
                    $purchaseOrder->number,
                    $request->user(),
                );

                $item->update(['quantity_received' => $item->quantity]);

                // optionally record the paid cost as the product's latest cost
                if ($updateCost && (float) $item->unit_cost > 0) {
                    $product->update(['cost_price' => $item->unit_cost]);
                }
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
        });

        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.product']));
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft purchase orders can be cancelled.'], 422);
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'items.product']));
    }

    private function syncItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        $purchaseOrder->items()->delete();

        $total = 0;

        foreach ($items as $item) {
            $unitCost = (float) ($item['unit_cost'] ?? 0);

            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'quantity_received' => 0,
                'unit_cost' => $unitCost,
            ]);

            $total += $item['quantity'] * $unitCost;
        }

        $purchaseOrder->update(['total' => $total]);
    }
}
