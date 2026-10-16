<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function products()
    {
        $products = Product::query()
            ->with('category')
            ->withSum('stocks as stock_total', 'quantity')
            ->orderBy('name')
            ->get();

        return $this->csv('products.csv', [
            'SKU', 'Name', 'Category', 'Unit', 'Cost', 'Sell', 'Reorder level', 'Stock total', 'Active', 'Barcode',
        ], $products->map(fn ($product) => [
            $product->sku,
            $product->name,
            $product->category?->name,
            $product->unit,
            $product->cost_price,
            $product->sell_price,
            $product->reorder_level,
            (int) ($product->stock_total ?? 0),
            $product->is_active ? 'yes' : 'no',
            $product->barcode,
        ]));
    }

    public function stockMovements(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->orderByDesc('id')
            ->get();

        return $this->csv('stock-movements.csv', [
            'Date', 'Product', 'SKU', 'Warehouse', 'Type', 'Quantity', 'Balance after', 'Reference', 'Note', 'User',
        ], $movements->map(fn ($movement) => [
            optional($movement->created_at)->toDateTimeString(),
            $movement->product?->name,
            $movement->product?->sku,
            $movement->warehouse?->name,
            $movement->type,
            $movement->quantity,
            $movement->balance_after,
            $movement->reference,
            $movement->note,
            $movement->user?->name,
        ]));
    }

    private function csv(string $filename, array $headings, iterable $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
