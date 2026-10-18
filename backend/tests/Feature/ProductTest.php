<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_products_get_sequential_skus(): void
    {
        $first = $this->apiPost('/api/products', ['name' => 'Alpha', 'unit' => 'pcs'])
            ->assertCreated();

        $this->assertSame('PRD-00001', $first->json('data.sku'));

        $second = $this->apiPost('/api/products', ['name' => 'Beta', 'unit' => 'pcs'])
            ->assertCreated();

        $this->assertSame('PRD-00002', $second->json('data.sku'));
    }

    public function test_an_explicit_sku_is_kept(): void
    {
        $this->apiPost('/api/products', ['name' => 'Gamma', 'unit' => 'pcs', 'sku' => 'CUSTOM-9'])
            ->assertCreated()
            ->assertJsonPath('data.sku', 'CUSTOM-9');
    }

    public function test_a_duplicate_sku_is_rejected(): void
    {
        $this->makeProduct(['sku' => 'DUP-1']);

        $this->apiPost('/api/products', ['name' => 'Dupe', 'unit' => 'pcs', 'sku' => 'DUP-1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    public function test_delete_frees_the_sku_and_barcode_for_reuse(): void
    {
        $product = $this->makeProduct(['sku' => 'ABC-1', 'barcode' => '111']);

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => "ABC-1::deleted::{$product->id}",
            'barcode' => "111::deleted::{$product->id}",
        ]);

        $this->apiPost('/api/products', [
            'name' => 'Reuse',
            'unit' => 'pcs',
            'sku' => 'ABC-1',
            'barcode' => '111',
        ])->assertCreated()->assertJsonPath('data.sku', 'ABC-1');
    }

    public function test_restore_brings_back_the_original_sku(): void
    {
        $product = $this->makeProduct(['sku' => 'REST-1']);

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();

        $this->apiPost("/api/products/{$product->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.sku', 'REST-1');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'REST-1', 'deleted_at' => null]);
    }

    public function test_restore_fails_when_the_sku_has_been_taken(): void
    {
        $product = $this->makeProduct(['sku' => 'TAKEN-1']);

        $this->apiDelete("/api/products/{$product->id}");

        $this->makeProduct(['sku' => 'TAKEN-1']);

        $this->apiPost("/api/products/{$product->id}/restore")->assertStatus(422);
    }

    public function test_a_product_with_stock_cannot_be_deleted(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 3,
        ])->assertCreated();

        $this->apiDelete("/api/products/{$product->id}")->assertStatus(422);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_force_delete_removes_a_product_without_history(): void
    {
        $product = $this->makeProduct();

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();
        $this->apiDelete("/api/products/{$product->id}/force")->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_force_delete_is_blocked_when_stock_history_exists(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 5,
        ])->assertCreated();

        // bring the level back to zero so the product can be soft deleted
        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'out',
            'quantity' => 5,
        ])->assertCreated();

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();
        $this->apiDelete("/api/products/{$product->id}/force")->assertStatus(422);
    }

    public function test_force_delete_is_blocked_when_sales_orders_exist(): void
    {
        $product = $this->makeProduct();
        $customer = $this->makeCustomer();

        $so = \App\Models\SalesOrder::create([
            'customer_id' => $customer->id,
            'number' => 'SO-TEST-1',
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'created_by' => $this->apiUser->id,
            'subtotal' => 10,
            'tax' => 0,
            'discount' => 0,
            'total' => 10,
        ]);

        \App\Models\SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);

        $this->apiDelete("/api/products/{$product->id}")->assertNoContent();
        $this->apiDelete("/api/products/{$product->id}/force")->assertStatus(422);
    }

    public function test_a_product_can_be_looked_up_by_barcode_or_sku(): void
    {
        $product = $this->makeProduct(['sku' => 'SCAN-1', 'barcode' => '5901234123457']);

        $this->apiGet('/api/products/lookup', ['code' => '5901234123457'])
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);

        $this->apiGet('/api/products/lookup', ['code' => 'SCAN-1'])
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_lookup_returns_404_for_an_unknown_code(): void
    {
        $this->apiGet('/api/products/lookup', ['code' => 'nope'])->assertStatus(404);
    }

    public function test_lookup_requires_a_code(): void
    {
        $this->apiGet('/api/products/lookup')->assertStatus(422);
    }
}
