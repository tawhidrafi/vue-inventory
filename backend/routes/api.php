<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;


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
});
