<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;

class ReportController extends Controller
{
    public function lowStock()
    {
        $products = Product::query()
            ->with('category')
            ->withSum('stocks as stock_total', 'quantity')
            ->orderBy('name')
            ->get()
            ->filter(fn ($p) => $p->reorder_level > 0 && (int) ($p->stock_total ?? 0) <= $p->reorder_level)
            ->values();

        return ProductResource::collection($products);
    }

    public function summary()
    {
        $products = Product::query()
            ->withSum('stocks as stock_total', 'quantity')
            ->get();

        return response()->json([
            'products' => $products->count(),
            'categories' => Category::count(),
            'suppliers' => Supplier::count(),
            'warehouses' => Warehouse::count(),
            'low_stock' => $products->filter(fn ($p) => $p->reorder_level > 0 && (int) ($p->stock_total ?? 0) <= $p->reorder_level)->count(),
            'total_units' => (int) Stock::sum('quantity'),
        ]);
    }
}
