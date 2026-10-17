<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_creating_a_sales_order_assigns_a_number_and_totals(): void
    {
        $customer = $this->makeCustomer();
        $productA = $this->makeProduct(['sell_price' => 20]);
        $productB = $this->makeProduct(['sell_price' => 50]);

        $response = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'discount' => 10,
            'tax' => 5,
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2, 'unit_price' => 20],
                ['product_id' => $productB->id, 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.number', 'SO-00001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal', '90.00')
            ->assertJsonPath('data.discount', '10.00')
            ->assertJsonPath('data.tax', '5.00')
            ->assertJsonPath('data.total', '85.00');

        $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $productA->id, 'quantity' => 1, 'unit_price' => 20]],
        ])->assertCreated()->assertJsonPath('data.number', 'SO-00002');
    }

    public function test_a_sales_order_requires_at_least_one_item(): void
    {
        $customer = $this->makeCustomer();

        $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_draft_sales_order_can_be_edited_and_deleted(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct(['sell_price' => 15]);

        $order = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 15]],
        ])->json('data');

        $this->apiPut("/api/sales-orders/{$order['id']}", [
            'customer_id' => $customer->id,
            'notes' => 'Urgent shipping',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 15]],
        ])->assertOk()
            ->assertJsonPath('data.notes', 'Urgent shipping')
            ->assertJsonPath('data.total', '45.00');

        $this->apiDelete("/api/sales-orders/{$order['id']}")->assertNoContent();
        $this->assertSoftDeleted('sales_orders', ['id' => $order['id']]);
    }

    public function test_confirming_a_sales_order(): void
    {
        $order = $this->draftOrder();

        $response = $this->apiPost("/api/sales-orders/{$order['id']}/confirm");

        $response->assertOk()->assertJsonPath('data.status', 'confirmed');
    }

    public function test_shipping_decreases_stock_and_completes_the_order(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->stock($product, $warehouse, 20);

        $customer = $this->makeCustomer();
        $order = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10]],
        ])->json('data');

        $response = $this->apiPost("/api/sales-orders/{$order['id']}/ship", [
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.items.0.quantity_shipped', 5)
            ->assertJsonPath('data.items.0.fully_shipped', true);

        $this->assertNotNull($response->json('data.shipped_at'));

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'out',
            'quantity' => -5,
            'balance_after' => 15,
        ]);
    }

    public function test_partial_shipping_workflow(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->stock($product, $warehouse, 10);

        $customer = $this->makeCustomer();
        $order = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 10]],
        ])->json('data');

        $itemId = $order['items'][0]['id'];

        // Ship 2 units first
        $response1 = $this->apiPost("/api/sales-orders/{$order['id']}/ship", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $itemId, 'quantity' => 2]],
        ])->assertOk();

        $response1->assertJsonPath('data.status', 'partially_shipped')
            ->assertJsonPath('data.items.0.quantity_shipped', 2)
            ->assertJsonPath('data.items.0.quantity_remaining', 4);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
        ]);

        // Ship the remaining 4 units
        $response2 = $this->apiPost("/api/sales-orders/{$order['id']}/ship", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $itemId, 'quantity' => 4]],
        ])->assertOk();

        $response2->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.items.0.quantity_shipped', 6)
            ->assertJsonPath('data.items.0.fully_shipped', true);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
        ]);
    }

    public function test_shipping_fails_if_insufficient_stock(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->stock($product, $warehouse, 2);

        $customer = $this->makeCustomer();
        $order = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10]],
        ])->json('data');

        $this->apiPost("/api/sales-orders/{$order['id']}/ship", [
            'warehouse_id' => $warehouse->id,
        ])->assertStatus(422)->assertJsonValidationErrors('items.'.$order['items'][0]['id']);
    }

    public function test_shipping_cannot_exceed_remaining_order_quantity(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->stock($product, $warehouse, 20);

        $customer = $this->makeCustomer();
        $order = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10]],
        ])->json('data');

        $itemId = $order['items'][0]['id'];

        $this->apiPost("/api/sales-orders/{$order['id']}/ship", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $itemId, 'quantity' => 6]],
        ])->assertStatus(422)->assertJsonValidationErrors("items.{$itemId}");
    }

    public function test_cancelling_sales_orders(): void
    {
        $order = $this->draftOrder();

        $this->apiPost("/api/sales-orders/{$order['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_shipped_sales_order(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        $this->stock($product, $warehouse, 10);

        $customer = $this->makeCustomer();
        $order = $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 10]],
        ])->json('data');

        $this->apiPost("/api/sales-orders/{$order['id']}/ship", [
            'warehouse_id' => $warehouse->id,
        ])->assertOk();

        $this->apiPost("/api/sales-orders/{$order['id']}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot cancel a sales order that has already shipped items.');
    }

    private function draftOrder(): array
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();

        return $this->apiPost('/api/sales-orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 10]],
        ])->json('data');
    }

    private function stock(Product $product, Warehouse $warehouse, int $quantity): void
    {
        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => $quantity,
        ])->assertCreated();
    }
}
