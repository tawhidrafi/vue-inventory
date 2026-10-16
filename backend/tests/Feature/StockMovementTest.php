<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_stock_in_increases_the_level(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 8,
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'in')
            ->assertJsonPath('data.quantity', 8)
            ->assertJsonPath('data.balance_after', 8);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
        ]);
    }

    public function test_stock_out_decreases_the_level(): void
    {
        [$product, $warehouse] = $this->stockedProduct(10);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'out',
            'quantity' => 4,
        ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', -4)
            ->assertJsonPath('data.balance_after', 6);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 6,
        ]);
    }

    public function test_stock_out_beyond_available_is_rejected(): void
    {
        [$product, $warehouse] = $this->stockedProduct(3);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'out',
            'quantity' => 5,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');

        // the level is untouched
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);
    }

    public function test_an_adjustment_can_be_negative(): void
    {
        [$product, $warehouse] = $this->stockedProduct(10);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'adjustment',
            'quantity' => -3,
            'note' => 'Damaged goods',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', -3)
            ->assertJsonPath('data.balance_after', 7);
    }

    public function test_an_adjustment_requires_a_note(): void
    {
        [$product, $warehouse] = $this->stockedProduct(5);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'adjustment',
            'quantity' => -1,
        ])->assertStatus(422)->assertJsonValidationErrors('note');
    }

    public function test_a_zero_quantity_is_rejected(): void
    {
        [$product, $warehouse] = $this->stockedProduct(5);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    public function test_the_low_stock_report_ignores_products_without_a_reorder_level(): void
    {
        // no reorder level -> never low
        $this->stockedProduct(0);

        // reorder level 5, stock 0 -> low
        $product = $this->makeProduct(['reorder_level' => 5]);

        $response = $this->apiGet('/api/reports/low-stock')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($product->id));
        $this->assertCount(1, $ids);
    }

    /**
     * @return array{0: Product, 1: Warehouse}
     */
    private function stockedProduct(int $quantity): array
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        if ($quantity > 0) {
            $this->apiPost('/api/stock-movements', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'quantity' => $quantity,
            ])->assertCreated();
        }

        return [$product, $warehouse];
    }
}
