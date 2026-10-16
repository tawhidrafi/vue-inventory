<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\StockResource;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::query()
            ->with(['product.category', 'warehouse'])
            ->when($request->search, function ($q, $s) {
                $q->whereHas('product', function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('sku', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->boolean('low_stock'), function ($q) {
                // products whose total across all warehouses is at or below reorder level
                $q->whereIn('product_id', function ($sub) {
                    $sub->from('stocks as s')
                        ->join('products as p', 'p.id', '=', 's.product_id')
                        ->groupBy('s.product_id', 'p.reorder_level')
                        ->havingRaw('p.reorder_level > 0 AND SUM(s.quantity) <= p.reorder_level')
                        ->select('s.product_id');
                });
            })
            ->orderBy('product_id')
            ->orderBy('warehouse_id');

        if ($request->boolean('all')) {
            return StockResource::collection($query->get());
        }

        return StockResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function forProduct(Product $product)
    {
        $stocks = $product->stocks()->with('warehouse')->orderBy('warehouse_id')->get();

        return [
            'product' => new ProductResource($product->load('category')),
            'total' => (int) $product->stocks()->sum('quantity'),
            'reorder_level' => $product->reorder_level,
            'stocks' => StockResource::collection($stocks),
        ];
    }
}
