<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;

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
            'customers' => Customer::count(),
            'sales_orders' => SalesOrder::count(),
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

    /**
     * Comprehensive dashboard overview metrics, distributions, and recent operations.
     */
    public function dashboard(Request $request)
    {
        $products = Product::query()
            ->with('category')
            ->withSum('stocks as stock_total', 'quantity')
            ->get();

        $totalUnits = (int) Stock::sum('quantity');

        // Valuation calculations
        $stocks = Stock::query()
            ->with(['warehouse', 'product.category'])
            ->where('quantity', '>', 0)
            ->whereHas('product')
            ->get();

        $costValue = 0.0;
        $retailValue = 0.0;
        $warehouseMap = [];
        $categoryMap = [];

        foreach ($stocks as $stock) {
            $product = $stock->product;
            $quantity = (int) $stock->quantity;
            $cost = (float) $product->cost_price * $quantity;
            $retail = (float) $product->sell_price * $quantity;

            $costValue += $cost;
            $retailValue += $retail;

            $wId = $stock->warehouse_id;
            $warehouseMap[$wId] ??= [
                'warehouse_id' => $wId,
                'warehouse_name' => $stock->warehouse?->name ?? 'Unknown',
                'units' => 0,
                'cost_value' => 0.0,
                'retail_value' => 0.0,
                'products_count' => 0,
            ];
            $warehouseMap[$wId]['units'] += $quantity;
            $warehouseMap[$wId]['cost_value'] += $cost;
            $warehouseMap[$wId]['retail_value'] += $retail;
            $warehouseMap[$wId]['products_count']++;

            $cId = $product->category_id ?? 0;
            $categoryMap[$cId] ??= [
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name ?? 'Uncategorised',
                'units' => 0,
                'cost_value' => 0.0,
                'retail_value' => 0.0,
                'products_count' => 0,
            ];
            $categoryMap[$cId]['units'] += $quantity;
            $categoryMap[$cId]['cost_value'] += $cost;
            $categoryMap[$cId]['retail_value'] += $retail;
            $categoryMap[$cId]['products_count']++;
        }

        // Add all warehouses so warehouses with 0 units still appear
        $allWarehouses = Warehouse::all();
        foreach ($allWarehouses as $w) {
            $warehouseMap[$w->id] ??= [
                'warehouse_id' => $w->id,
                'warehouse_name' => $w->name,
                'units' => 0,
                'cost_value' => 0.0,
                'retail_value' => 0.0,
                'products_count' => 0,
            ];
        }

        $roundWarehouse = function (array $row) use ($totalUnits): array {
            $row['cost_value'] = round($row['cost_value'], 2);
            $row['retail_value'] = round($row['retail_value'], 2);
            $row['share_percentage'] = $totalUnits > 0 ? round(($row['units'] / $totalUnits) * 100, 1) : 0;

            return $row;
        };

        $warehouseList = array_map($roundWarehouse, array_values($warehouseMap));
        usort($warehouseList, fn ($a, $b) => $b['units'] <=> $a['units']);

        $roundCategory = function (array $row) use ($totalUnits): array {
            $row['cost_value'] = round($row['cost_value'], 2);
            $row['retail_value'] = round($row['retail_value'], 2);
            $row['share_percentage'] = $totalUnits > 0 ? round(($row['units'] / $totalUnits) * 100, 1) : 0;

            return $row;
        };

        $categoryList = array_map($roundCategory, array_values($categoryMap));
        usort($categoryList, fn ($a, $b) => $b['cost_value'] <=> $a['cost_value']);

        // Low stock products
        $lowStockProducts = $products->filter(
            fn ($p) => $p->reorder_level > 0 && (int) ($p->stock_total ?? 0) <= $p->reorder_level
        )->values();

        $outOfStockCount = $products->filter(
            fn ($p) => (int) ($p->stock_total ?? 0) === 0
        )->count();

        // Top low stock items for dashboard widget (sorted by deficit)
        $topLowStock = $lowStockProducts->sortBy(
            fn ($p) => (int) ($p->stock_total ?? 0) - (int) $p->reorder_level
        )->take(10)->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'unit' => $p->unit,
            'category_name' => $p->category?->name ?? 'Uncategorised',
            'stock_total' => (int) ($p->stock_total ?? 0),
            'reorder_level' => (int) $p->reorder_level,
            'deficit' => (int) $p->reorder_level - (int) ($p->stock_total ?? 0),
            'cost_price' => (float) $p->cost_price,
            'sell_price' => (float) $p->sell_price,
        ])->values();

        // Sales Orders metrics & recents
        $salesOrdersTotal = SalesOrder::count();
        $salesPending = SalesOrder::whereIn('status', ['draft', 'confirmed', 'partially_shipped'])->count();
        $salesShipped = SalesOrder::where('status', 'shipped')->count();
        $salesRevenue = (float) SalesOrder::whereIn('status', ['confirmed', 'partially_shipped', 'shipped'])->sum('total');

        $recentSalesOrders = SalesOrder::with('customer')
            ->latest('id')
            ->take(6)
            ->get()
            ->map(fn ($so) => [
                'id' => $so->id,
                'number' => $so->number,
                'customer_name' => $so->customer?->name ?? $so->customer?->company_name ?? 'Walk-in',
                'order_date' => $so->order_date?->format('Y-m-d') ?? $so->created_at?->format('Y-m-d'),
                'status' => $so->status,
                'total' => (float) $so->total,
            ]);

        // Purchase Orders metrics & recents
        $purchaseOrdersTotal = PurchaseOrder::count();
        $purchasePending = PurchaseOrder::whereIn('status', ['draft', 'partially_received'])->count();
        $purchaseReceived = PurchaseOrder::where('status', 'received')->count();
        $purchaseSpend = (float) PurchaseOrder::whereIn('status', ['partially_received', 'received'])->sum('total');

        $recentPurchaseOrders = PurchaseOrder::with('supplier')
            ->latest('id')
            ->take(6)
            ->get()
            ->map(fn ($po) => [
                'id' => $po->id,
                'number' => $po->number,
                'supplier_name' => $po->supplier?->name ?? 'Supplier',
                'order_date' => $po->expected_at?->format('Y-m-d') ?? $po->created_at?->format('Y-m-d'),
                'status' => $po->status,
                'total' => (float) $po->total,
            ]);

        // Recent stock movements
        $recentMovements = StockMovement::with(['product', 'warehouse', 'user'])
            ->latest('id')
            ->take(6)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'product_name' => $m->product?->name ?? 'Product',
                'product_sku' => $m->product?->sku ?? '-',
                'warehouse_name' => $m->warehouse?->name ?? 'Warehouse',
                'user_name' => $m->user?->name ?? 'System',
                'type' => $m->type,
                'quantity' => $m->quantity,
                'balance_after' => $m->balance_after,
                'reference' => $m->reference,
                'created_at' => $m->created_at?->toIso8601String(),
            ]);

        // Recent activity logs (if user is admin or manager)
        $user = $request->user();
        $canViewLogs = $user && in_array($user->role, ['admin', 'manager'], true);
        $recentActivities = $canViewLogs
            ? ActivityLog::latest('id')
                ->take(6)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'user_name' => $a->user_name ?? 'System',
                    'action' => $a->action,
                    'subject_type' => class_basename($a->subject_type),
                    'description' => $a->description,
                    'created_at' => $a->created_at?->toIso8601String(),
                ])
            : [];

        // 30 days stock movements aggregation
        $sinceDate = now()->subDays(30);
        $movements30Days = StockMovement::where('created_at', '>=', $sinceDate)->get();
        $inUnits30Days = $movements30Days->where('quantity', '>', 0)->sum('quantity');
        $outUnits30Days = abs($movements30Days->where('quantity', '<', 0)->sum('quantity'));

        return response()->json([
            'metrics' => [
                'products_count' => $products->count(),
                'categories_count' => Category::count(),
                'suppliers_count' => Supplier::count(),
                'customers_count' => Customer::count(),
                'warehouses_count' => $allWarehouses->count(),
                'total_units' => $totalUnits,
                'cost_value' => round($costValue, 2),
                'retail_value' => round($retailValue, 2),
                'potential_margin' => round($retailValue - $costValue, 2),
                'margin_percentage' => $retailValue > 0 ? round((($retailValue - $costValue) / $retailValue) * 100, 1) : 0,
                'low_stock_count' => $lowStockProducts->count(),
                'out_of_stock_count' => $outOfStockCount,
                'sales_orders' => [
                    'total' => $salesOrdersTotal,
                    'pending' => $salesPending,
                    'shipped' => $salesShipped,
                    'revenue' => round($salesRevenue, 2),
                ],
                'purchase_orders' => [
                    'total' => $purchaseOrdersTotal,
                    'pending' => $purchasePending,
                    'received' => $purchaseReceived,
                    'spend' => round($purchaseSpend, 2),
                ],
                'movements_last_30_days' => [
                    'in_units' => $inUnits30Days,
                    'out_units' => $outUnits30Days,
                    'total_count' => $movements30Days->count(),
                ],
            ],
            'low_stock_items' => $topLowStock,
            'warehouses' => $warehouseList,
            'categories' => $categoryList,
            'recent_sales_orders' => $recentSalesOrders,
            'recent_purchase_orders' => $recentPurchaseOrders,
            'recent_movements' => $recentMovements,
            'recent_activities' => $recentActivities,
        ]);
    }
}
