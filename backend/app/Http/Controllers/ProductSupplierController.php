<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSupplierRequest;
use App\Http\Resources\ProductSupplierResource;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class ProductSupplierController extends Controller
{
    public function index(Product $product)
    {
        return ProductSupplierResource::collection(
            $product->suppliers()->orderBy('name')->get()
        );
    }

    public function store(ProductSupplierRequest $request, Product $product)
    {
        $data = $request->validated();

        if (! empty($data['is_preferred'])) {
            $this->clearPreferred($product);
        }

        $product->suppliers()->syncWithoutDetaching([
            $data['supplier_id'] => [
                'supplier_sku' => $data['supplier_sku'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'lead_time_days' => $data['lead_time_days'] ?? null,
                'min_order_quantity' => $data['min_order_quantity'] ?? null,
                'is_preferred' => $data['is_preferred'] ?? false,
            ],
        ]);

        $supplier = $product->suppliers()->whereKey($data['supplier_id'])->first();

        return (new ProductSupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProductSupplierRequest $request, Product $product, Supplier $supplier)
    {
        $data = $request->validated();

        if (! empty($data['is_preferred'])) {
            $this->clearPreferred($product);
        }

        $product->suppliers()->updateExistingPivot($supplier->id, [
            'supplier_sku' => $data['supplier_sku'] ?? null,
            'unit_cost' => $data['unit_cost'] ?? null,
            'lead_time_days' => $data['lead_time_days'] ?? null,
            'min_order_quantity' => $data['min_order_quantity'] ?? null,
            'is_preferred' => $data['is_preferred'] ?? false,
        ]);

        return new ProductSupplierResource($product->suppliers()->whereKey($supplier->id)->first());
    }

    public function destroy(Product $product, Supplier $supplier)
    {
        $product->suppliers()->detach($supplier->id);

        return response()->noContent();
    }

    private function clearPreferred(Product $product): void
    {
        DB::table('product_supplier')
            ->where('product_id', $product->id)
            ->update(['is_preferred' => false]);
    }
}
