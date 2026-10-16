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

    /**
     * Inventory valuation at cost and at retail, split by warehouse and category.
     */
    public function valuation()
    {
        $stocks = Stock::query()
            ->with(['warehouse', 'product.category'])
            ->where('quantity', '>', 0)
            ->whereHas('product')
            ->get();

        $units = 0;
        $costValue = 0.0;
        $retailValue = 0.0;
        $byWarehouse = [];
        $byCategory = [];

        foreach ($stocks as $stock) {
            $product = $stock->product;
            $quantity = (int) $stock->quantity;
            $cost = (float) $product->cost_price * $quantity;
            $retail = (float) $product->sell_price * $quantity;

            $units += $quantity;
            $costValue += $cost;
            $retailValue += $retail;

            $warehouseId = $stock->warehouse_id;
            $byWarehouse[$warehouseId] ??= [
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $stock->warehouse?->name,
                'units' => 0,
                'cost_value' => 0.0,
                'retail_value' => 0.0,
            ];
            $byWarehouse[$warehouseId]['units'] += $quantity;
            $byWarehouse[$warehouseId]['cost_value'] += $cost;
            $byWarehouse[$warehouseId]['retail_value'] += $retail;

            $categoryId = $product->category_id ?? 0;
            $byCategory[$categoryId] ??= [
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name ?? 'Uncategorised',
                'units' => 0,
                'cost_value' => 0.0,
                'retail_value' => 0.0,
            ];
            $byCategory[$categoryId]['units'] += $quantity;
            $byCategory[$categoryId]['cost_value'] += $cost;
            $byCategory[$categoryId]['retail_value'] += $retail;
        }

        $round = function (array $row): array {
            $row['cost_value'] = round($row['cost_value'], 2);
            $row['retail_value'] = round($row['retail_value'], 2);

            return $row;
        };

        $byWarehouse = array_map($round, array_values($byWarehouse));
        usort($byWarehouse, fn ($a, $b) => $b['cost_value'] <=> $a['cost_value']);

        $byCategory = array_map($round, array_values($byCategory));
        usort($byCategory, fn ($a, $b) => $b['cost_value'] <=> $a['cost_value']);

        return response()->json([
            'total_units' => $units,
            'cost_value' => round($costValue, 2),
            'retail_value' => round($retailValue, 2),
            'potential_margin' => round($retailValue - $costValue, 2),
            'by_warehouse' => $byWarehouse,
            'by_category' => $byCategory,
        ]);
    }
}
