<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'Laravel is working']);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth.token')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('warehouses', WarehouseController::class);

    Route::get('products/{product}/stock', [StockController::class, 'forProduct']);
    Route::get('stocks', [StockController::class, 'index']);
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);
    Route::post('stock-transfers', [StockTransferController::class, 'store']);
    Route::get('reports/low-stock', [ReportController::class, 'lowStock']);
    Route::get('reports/summary', [ReportController::class, 'summary']);
    Route::get('reports/valuation', [ReportController::class, 'valuation']);

    Route::get('products/{product}/suppliers', [ProductSupplierController::class, 'index']);
    Route::post('products/{product}/suppliers', [ProductSupplierController::class, 'store']);
    Route::put('products/{product}/suppliers/{supplier}', [ProductSupplierController::class, 'update']);
    Route::delete('products/{product}/suppliers/{supplier}', [ProductSupplierController::class, 'destroy']);

    Route::get('suppliers/{supplier}/products', [SupplierController::class, 'products']);

    Route::post('products/{id}/restore', [ProductController::class, 'restore']);
    Route::delete('products/{id}/force', [ProductController::class, 'forceDestroy']);
    Route::post('categories/{id}/restore', [CategoryController::class, 'restore']);
    Route::delete('categories/{id}/force', [CategoryController::class, 'forceDestroy']);

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
    Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
});
