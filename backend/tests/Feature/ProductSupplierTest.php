<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class ProductSupplierTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_a_supplier_can_be_attached_to_a_product(): void
    {
        $product = $this->makeProduct();
        $supplier = $this->makeSupplier();

        $this->apiPost("/api/products/{$product->id}/suppliers", [
            'supplier_id' => $supplier->id,
            'supplier_sku' => 'SUP-XYZ',
            'unit_cost' => 4.5,
        ])
            ->assertCreated()
            ->assertJsonPath('data.supplier_sku', 'SUP-XYZ')
            ->assertJsonPath('data.unit_cost', 4.5);

        $this->assertDatabaseHas('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_only_one_supplier_can_be_preferred(): void
    {
        $product = $this->makeProduct();
        $first = $this->makeSupplier();
        $second = $this->makeSupplier();

        $this->apiPost("/api/products/{$product->id}/suppliers", [
            'supplier_id' => $first->id,
            'is_preferred' => true,
        ])->assertCreated();

        $this->apiPost("/api/products/{$product->id}/suppliers", [
            'supplier_id' => $second->id,
            'is_preferred' => true,
        ])->assertCreated();

        $this->assertFalse((bool) $product->suppliers()->whereKey($first->id)->first()->pivot->is_preferred);
        $this->assertTrue((bool) $product->suppliers()->whereKey($second->id)->first()->pivot->is_preferred);
    }

    public function test_a_link_can_be_updated(): void
    {
        $product = $this->makeProduct();
        $supplier = $this->makeSupplier();

        $this->apiPost("/api/products/{$product->id}/suppliers", [
            'supplier_id' => $supplier->id,
            'unit_cost' => 4,
        ])->assertCreated();

        $this->apiPut("/api/products/{$product->id}/suppliers/{$supplier->id}", [
            'unit_cost' => 9,
            'lead_time_days' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('data.unit_cost', 9)
            ->assertJsonPath('data.lead_time_days', 3);
    }

    public function test_a_supplier_can_be_detached_from_a_product(): void
    {
        $product = $this->makeProduct();
        $supplier = $this->makeSupplier();

        $this->apiPost("/api/products/{$product->id}/suppliers", [
            'supplier_id' => $supplier->id,
        ])->assertCreated();

        $this->apiDelete("/api/products/{$product->id}/suppliers/{$supplier->id}")->assertNoContent();

        $this->assertDatabaseMissing('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_a_supplier_with_purchase_orders_cannot_be_deleted(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();

        $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 1]],
        ])->assertCreated();

        $this->apiDelete("/api/suppliers/{$supplier->id}")->assertStatus(422);
    }
}
