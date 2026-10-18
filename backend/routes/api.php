<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSupplierController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'Laravel is working']);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth.token')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/preferences', [ProfileController::class, 'updatePreferences']);

    // ---- reads: available to every authenticated user ----
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/lookup', [ProductController::class, 'lookup']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);

    Route::get('products/{product}/stock', [StockController::class, 'forProduct']);
    Route::get('stocks', [StockController::class, 'index']);
    Route::get('stock-movements', [StockMovementController::class, 'index']);

    Route::get('products/{product}/suppliers', [ProductSupplierController::class, 'index']);
    Route::get('suppliers/{supplier}/products', [SupplierController::class, 'products']);

    Route::get('reports/low-stock', [ReportController::class, 'lowStock']);
    Route::get('reports/summary', [ReportController::class, 'summary']);
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/valuation', [ReportController::class, 'valuation']);

    Route::get('exports/products', [ExportController::class, 'products']);
    Route::get('exports/stock-movements', [ExportController::class, 'stockMovements']);

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);

    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);

    Route::get('sales-orders', [SalesOrderController::class, 'index']);
    Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show']);

    // ---- daily operations: available to every authenticated user ----
    Route::post('stock-movements', [StockMovementController::class, 'store']);
    Route::post('stock-transfers', [StockTransferController::class, 'store']);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
    Route::post('sales-orders/{salesOrder}/ship', [SalesOrderController::class, 'ship']);

    // ---- management: admins and managers ----
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('categories/{id}/restore', [CategoryController::class, 'restore']);
        Route::delete('categories/{id}/force', [CategoryController::class, 'forceDestroy']);

        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
        Route::post('products/{id}/restore', [ProductController::class, 'restore']);
        Route::delete('products/{id}/force', [ProductController::class, 'forceDestroy']);

        Route::post('products/{product}/suppliers', [ProductSupplierController::class, 'store']);
        Route::put('products/{product}/suppliers/{supplier}', [ProductSupplierController::class, 'update']);
        Route::delete('products/{product}/suppliers/{supplier}', [ProductSupplierController::class, 'destroy']);

        Route::post('suppliers', [SupplierController::class, 'store']);
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);

        Route::post('customers', [CustomerController::class, 'store']);
        Route::put('customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);

        Route::post('warehouses', [WarehouseController::class, 'store']);
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);

        Route::post('sales-orders', [SalesOrderController::class, 'store']);
        Route::put('sales-orders/{salesOrder}', [SalesOrderController::class, 'update']);
        Route::delete('sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy']);
        Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);
        Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel']);

        Route::get('activity-logs', [ActivityLogController::class, 'index']);
    });

    // ---- user management: admins only ----
    Route::middleware('role:admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });
});
