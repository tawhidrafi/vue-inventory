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
