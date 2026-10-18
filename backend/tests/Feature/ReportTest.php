<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_valuation_sums_cost_and_retail_by_warehouse_and_category(): void
    {
        $electronics = $this->makeCategory(['name' => 'Electronics']);
        $warehouseA = $this->makeWarehouse(['name' => 'A']);
        $warehouseB = $this->makeWarehouse(['name' => 'B']);

        $first = $this->makeProduct(['category_id' => $electronics->id, 'cost_price' => 10, 'sell_price' => 15]);
        $second = $this->makeProduct(['cost_price' => 5, 'sell_price' => 8]);

        $this->stock($first, $warehouseA, 4);
        $this->stock($second, $warehouseB, 2);

        $response = $this->apiGet('/api/reports/valuation')->assertOk();

        $response->assertJsonPath('total_units', 6)
            ->assertJsonPath('cost_value', 50)
            ->assertJsonPath('retail_value', 76)
            ->assertJsonPath('potential_margin', 26);

        $this->assertCount(2, $response->json('by_warehouse'));
        $this->assertCount(2, $response->json('by_category'));
    }

    public function test_valuation_ignores_zero_quantity_rows(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct(['cost_price' => 10, 'sell_price' => 15]);

        $this->stock($product, $warehouse, 5);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'out',
            'quantity' => 5,
        ])->assertCreated();

        $this->apiGet('/api/reports/valuation')
            ->assertOk()
            ->assertJsonPath('total_units', 0)
            ->assertJsonPath('cost_value', 0)
            ->assertJsonPath('by_warehouse', []);
    }

    public function test_dashboard_returns_metrics_and_breakdowns(): void
    {
        $electronics = $this->makeCategory(['name' => 'Electronics']);
        $warehouse = $this->makeWarehouse(['name' => 'Central Hub']);
        $customer = $this->makeCustomer(['name' => 'Jane Cooper']);
        $supplier = $this->makeSupplier(['name' => 'Supplier Alpha']);

        $product = $this->makeProduct([
            'category_id' => $electronics->id,
            'cost_price' => 20,
            'sell_price' => 35,
            'reorder_level' => 10,
        ]);

        // Stock 5 units (below reorder level of 10)
        $this->stock($product, $warehouse, 5);

        // Create a sales order
        \App\Models\SalesOrder::create([
            'customer_id' => $customer->id,
            'number' => 'SO-00001',
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'created_by' => $this->apiUser->id,
            'subtotal' => 70,
            'tax' => 0,
            'discount' => 0,
            'total' => 70,
        ]);

        // Create a purchase order
        \App\Models\PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'number' => 'PO-00001',
            'status' => 'received',
            'expected_at' => now()->toDateString(),
            'created_by' => $this->apiUser->id,
            'total' => 100,
        ]);

        \App\Models\ActivityLog::create([
            'user_id' => $this->apiUser->id,
            'user_name' => $this->apiUser->name,
            'action' => 'created',
            'subject_type' => \App\Models\Product::class,
            'subject_id' => $product->id,
            'description' => 'Created product',
            'created_at' => now(),
        ]);

        $response = $this->apiGet('/api/reports/dashboard')->assertOk();

        $response->assertJsonStructure([
            'metrics' => [
                'products_count',
                'categories_count',
                'suppliers_count',
                'customers_count',
                'warehouses_count',
                'total_units',
                'cost_value',
                'retail_value',
                'potential_margin',
                'margin_percentage',
                'low_stock_count',
                'out_of_stock_count',
                'sales_orders' => ['total', 'pending', 'shipped', 'revenue'],
                'purchase_orders' => ['total', 'pending', 'received', 'spend'],
                'movements_last_30_days' => ['in_units', 'out_units', 'total_count'],
            ],
            'low_stock_items',
            'warehouses',
            'categories',
            'recent_sales_orders',
            'recent_purchase_orders',
            'recent_movements',
            'recent_activities',
        ]);

        $this->assertEquals(1, $response->json('metrics.products_count'));
        $this->assertEquals(5, $response->json('metrics.total_units'));
        $this->assertEquals(100.0, $response->json('metrics.cost_value'));
        $this->assertEquals(175.0, $response->json('metrics.retail_value'));
        $this->assertEquals(1, $response->json('metrics.low_stock_count'));
        $this->assertCount(1, $response->json('low_stock_items'));
        $this->assertCount(1, $response->json('recent_sales_orders'));
        $this->assertCount(1, $response->json('recent_purchase_orders'));
        $this->assertNotEmpty($response->json('recent_activities'));
    }

    public function test_dashboard_hides_activities_for_staff_user(): void
    {
        $this->authenticate('staff');

        $response = $this->apiGet('/api/reports/dashboard')->assertOk();

        $this->assertEquals([], $response->json('recent_activities'));
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
