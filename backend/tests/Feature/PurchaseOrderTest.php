<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_creating_a_purchase_order_assigns_a_number_and_total(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();

        $response = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 10],
                ['product_id' => $this->makeProduct()->id, 'quantity' => 1, 'unit_cost' => 5.5],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.number', 'PO-00001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '25.50');

        $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 1]],
        ])->assertCreated()->assertJsonPath('data.number', 'PO-00002');
    }

    public function test_a_purchase_order_requires_at_least_one_item(): void
    {
        $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $this->makeSupplier()->id,
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_receiving_increases_stock_and_completes_the_order(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 7, 'unit_cost' => 3]],
        ])->json('data');

        $response = $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.items.0.quantity_received', 7);

        $this->assertNotNull($response->json('data.received_at'));
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 7,
        ]);
    }

    public function test_receiving_requires_a_warehouse(): void
    {
        $order = $this->draftOrder();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('warehouse_id');
    }

    public function test_a_purchase_order_cannot_be_received_twice(): void
    {
        $warehouse = $this->makeWarehouse();
        $order = $this->draftOrder();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", ['warehouse_id' => $warehouse->id])
            ->assertOk();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", ['warehouse_id' => $warehouse->id])
            ->assertStatus(422);
    }

    public function test_a_non_draft_order_cannot_be_edited_or_deleted(): void
    {
        $warehouse = $this->makeWarehouse();
        $order = $this->draftOrder();
        $product = $this->makeProduct();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", ['warehouse_id' => $warehouse->id]);

        $this->apiPut("/api/purchase-orders/{$order['id']}", [
            'supplier_id' => $order['supplier_id'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->apiDelete("/api/purchase-orders/{$order['id']}")->assertStatus(422);
    }

    public function test_a_draft_order_can_be_deleted(): void
    {
        $order = $this->draftOrder();

        $this->apiDelete("/api/purchase-orders/{$order['id']}")->assertNoContent();
        $this->assertSoftDeleted('purchase_orders', ['id' => $order['id']]);
    }

    public function test_a_draft_order_can_be_cancelled(): void
    {
        $order = $this->draftOrder();

        $this->apiPost("/api/purchase-orders/{$order['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->apiPost("/api/purchase-orders/{$order['id']}/cancel")->assertStatus(422);
    }

    public function test_receiving_can_update_the_product_cost(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct(['cost_price' => 0]);
        $warehouse = $this->makeWarehouse();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 12.5]],
        ])->json('data');

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'update_cost' => true,
        ])->assertOk();

        $this->assertSame('12.50', Product::find($product->id)->cost_price);
    }

    public function test_a_purchase_order_can_be_received_in_parts(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 2]],
        ])->json('data');

        $itemId = $order['items'][0]['id'];

        $first = $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $itemId, 'quantity' => 4]],
        ]);

        $first->assertOk()
            ->assertJsonPath('data.status', 'partially_received')
            ->assertJsonPath('data.items.0.quantity_received', 4)
            ->assertJsonPath('data.items.0.quantity_remaining', 6);

        $this->assertNull($first->json('data.received_at'));
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
        ]);

        $second = $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $itemId, 'quantity' => 6]],
        ]);

        $second->assertOk()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.items.0.quantity_received', 10);

        $this->assertNotNull($second->json('data.received_at'));
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);
    }

    public function test_a_partial_receive_keeps_the_other_lines_untouched(): void
    {
        $supplier = $this->makeSupplier();
        $first = $this->makeProduct();
        $second = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $first->id, 'quantity' => 3, 'unit_cost' => 1],
                ['product_id' => $second->id, 'quantity' => 5, 'unit_cost' => 1],
            ],
        ])->json('data');

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $order['items'][0]['id'], 'quantity' => 3]],
        ])->assertOk()->assertJsonPath('data.status', 'partially_received');

        $this->assertDatabaseHas('stocks', ['product_id' => $first->id, 'quantity' => 3]);
        $this->assertDatabaseMissing('stocks', ['product_id' => $second->id]);
    }

    public function test_cannot_receive_more_than_remaining(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 1]],
        ])->json('data');

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $order['items'][0]['id'], 'quantity' => 6]],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('stocks', ['warehouse_id' => $warehouse->id]);
    }

    public function test_receiving_a_line_from_another_order_is_rejected(): void
    {
        $warehouse = $this->makeWarehouse();
        $order = $this->draftOrder();

        $other = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $this->makeSupplier()->id,
            'items' => [['product_id' => $this->makeProduct()->id, 'quantity' => 3, 'unit_cost' => 1]],
        ])->json('data');

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $other['items'][0]['id'], 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_receiving_nothing_is_rejected(): void
    {
        $warehouse = $this->makeWarehouse();
        $order = $this->draftOrder();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $order['items'][0]['id'], 'quantity' => 0]],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('stocks', ['warehouse_id' => $warehouse->id]);
    }

    public function test_a_partially_received_order_can_be_cancelled(): void
    {
        $warehouse = $this->makeWarehouse();
        $order = $this->draftOrder();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $order['items'][0]['id'], 'quantity' => 1]],
        ])->assertOk()->assertJsonPath('data.status', 'partially_received');

        $this->apiPost("/api/purchase-orders/{$order['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    /**
     * @return array<string, mixed>
     */
    private function draftOrder(): array
    {
        return $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $this->makeSupplier()->id,
            'items' => [['product_id' => $this->makeProduct()->id, 'quantity' => 2, 'unit_cost' => 4]],
        ])->assertCreated()->json('data');
    }
}
