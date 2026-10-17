<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;

trait CreatesModels
{
    protected function makeCategory(array $attributes = []): Category
    {
        return Category::create(array_merge(
            ['name' => 'Category '.uniqid()],
            $attributes,
        ));
    }

    protected function makeProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'PRD-'.strtoupper(uniqid()),
            'name' => 'Product '.uniqid(),
            'unit' => 'pcs',
        ], $attributes));
    }

    protected function makeWarehouse(array $attributes = []): Warehouse
    {
        return Warehouse::create(array_merge(
            ['name' => 'Warehouse '.uniqid()],
            $attributes,
        ));
    }

    protected function makeSupplier(array $attributes = []): Supplier
    {
        return Supplier::create(array_merge(
            ['name' => 'Supplier '.uniqid()],
            $attributes,
        ));
    }

    protected function makeCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge(
            ['name' => 'Customer '.uniqid()],
            $attributes,
        ));
    }
}
