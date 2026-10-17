<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesOrderRequest;
use App\Http\Requests\ShipSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::query()
            ->with('customer')
            ->withCount('items')
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->search, fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->orderByDesc('id');

        return SalesOrderResource::collection(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(SalesOrderRequest $request)
    {
        $data = $request->validated();

        $salesOrder = DB::transaction(function () use ($data, $request) {
            $salesOrder = SalesOrder::create([
                'customer_id' => $data['customer_id'],
                'number' => 'TMP-'.Str::upper(Str::random(20)),
                'status' => 'draft',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_ship_date' => $data['expected_ship_date'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
                'subtotal' => 0,
                'tax' => (float) ($data['tax'] ?? 0),
                'discount' => (float) ($data['discount'] ?? 0),
                'total' => 0,
            ]);

            $salesOrder->update([
                'number' => 'SO-'.str_pad((string) $salesOrder->id, 5, '0', STR_PAD_LEFT),
            ]);

            $this->syncItems(
                $salesOrder,
                $data['items'],
                (float) ($data['discount'] ?? 0),
                (float) ($data['tax'] ?? 0)
            );

            return $salesOrder;
        });

        return (new SalesOrderResource($salesOrder->load(['customer', 'items.product', 'creator'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SalesOrder $salesOrder)
    {
        return new SalesOrderResource($salesOrder->load(['customer', 'items.product', 'creator']));
    }

    public function update(SalesOrderRequest $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft sales orders can be edited.'], 422);
        }

        $data = $request->validated();

        DB::transaction(function () use ($salesOrder, $data) {
            $salesOrder->update([
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'] ?? $salesOrder->order_date,
                'expected_ship_date' => $data['expected_ship_date'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems(
                $salesOrder,
                $data['items'],
                (float) ($data['discount'] ?? 0),
                (float) ($data['tax'] ?? 0)
            );
        });

        return new SalesOrderResource($salesOrder->load(['customer', 'items.product', 'creator']));
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft sales orders can be deleted.'], 422);
        }

        $salesOrder->delete();

        return response()->noContent();
    }

    public function confirm(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return response()->json(['message' => 'Only draft sales orders can be confirmed.'], 422);
        }

        $salesOrder->update(['status' => 'confirmed']);

        return new SalesOrderResource($salesOrder->load(['customer', 'items.product', 'creator']));
    }

    public function ship(ShipSalesOrderRequest $request, SalesOrder $salesOrder, StockService $stock)
    {
        if (! in_array($salesOrder->status, ['draft', 'confirmed', 'partially_shipped'], true)) {
            return response()->json(['message' => 'This sales order cannot be shipped.'], 422);
        }

        $warehouse = Warehouse::findOrFail($request->validated()['warehouse_id']);
        $lines = $request->validated()['items'] ?? null;

        $salesOrder->load('items.product');

        $plan = [];

        if ($lines === null) {
            foreach ($salesOrder->items as $item) {
                $plan[$item->id] = $item->quantity - $item->quantity_shipped;
            }
        } else {
            foreach ($lines as $line) {
                $plan[(int) $line['id']] = (int) $line['quantity'];
            }
        }

        $errors = [];

        foreach ($plan as $itemId => $quantity) {
            $item = $salesOrder->items->firstWhere('id', $itemId);

            if (! $item) {
                $errors["items.{$itemId}"] = 'This line does not belong to the sales order.';

                continue;
            }

            $remaining = $item->quantity - $item->quantity_shipped;

            if ($quantity > $remaining) {
                $errors["items.{$itemId}"] = "Cannot ship {$quantity}; only {$remaining} remaining.";

                continue;
            }

            if ($quantity > 0) {
                $available = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $warehouse->id)
                    ->value('quantity') ?? 0;

                if ($available < $quantity) {
                    $productName = $item->product?->name ?? "Product #{$item->product_id}";
                    $errors["items.{$itemId}"] = "Insufficient stock for \"{$productName}\" in {$warehouse->name}: only {$available} available, {$quantity} requested.";
                }
            }
        }

        if ($errors) {
            return response()->json(['message' => 'Invalid ship quantities.', 'errors' => $errors], 422);
        }

        if (array_sum($plan) < 1) {
            return response()->json(['message' => 'There is nothing left to ship on this order.'], 422);
        }

        DB::transaction(function () use ($salesOrder, $warehouse, $stock, $request, $plan) {
            foreach ($salesOrder->items as $item) {
                $quantity = $plan[$item->id] ?? 0;

                if ($quantity < 1) {
                    continue;
                }

                $product = Product::withTrashed()->find($item->product_id);

                $stock->record(
                    $product,
                    $warehouse,
                    'out',
                    -$quantity,
                    "Shipped on {$salesOrder->number}",
                    $salesOrder->number,
                    $request->user(),
                );

                $item->update(['quantity_shipped' => $item->quantity_shipped + $quantity]);
            }

            $complete = $salesOrder->items->every(
                fn ($item) => $item->quantity_shipped >= $item->quantity
            );

            $salesOrder->update([
                'status' => $complete ? 'shipped' : 'partially_shipped',
                'shipped_at' => $complete ? now() : ($salesOrder->shipped_at ?? now()),
            ]);
        });

        $fresh = $salesOrder->fresh()->load(['customer', 'items.product', 'creator']);

        return new SalesOrderResource($fresh);
    }

    public function cancel(SalesOrder $salesOrder)
    {
        if ($salesOrder->items()->where('quantity_shipped', '>', 0)->exists()) {
            return response()->json(['message' => 'Cannot cancel a sales order that has already shipped items.'], 422);
        }

        if ($salesOrder->status === 'shipped') {
            return response()->json(['message' => 'Shipped sales orders cannot be cancelled.'], 422);
        }

        $salesOrder->update(['status' => 'cancelled']);

        return new SalesOrderResource($salesOrder->load(['customer', 'items.product', 'creator']));
    }

    private function syncItems(SalesOrder $salesOrder, array $items, float $discount = 0, float $tax = 0): void
    {
        $salesOrder->items()->delete();

        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $unitPrice = isset($item['unit_price']) && $item['unit_price'] !== '' && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) ($product?->sell_price ?? 0);

            $quantity = (int) $item['quantity'];
            $lineTotal = round($quantity * $unitPrice, 2);

            $salesOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'quantity_shipped' => 0,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $subtotal += $lineTotal;
        }

        $total = max(0, round($subtotal - $discount + $tax, 2));

        $salesOrder->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ]);
    }
}
