<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_a_transfer_moves_stock_and_records_two_linked_movements(): void
    {
        $product = $this->makeProduct();
        $from = $this->makeWarehouse();
        $to = $this->makeWarehouse();

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $from->id,
            'type' => 'in',
            'quantity' => 10,
        ])->assertCreated();

        $this->apiPost('/api/stock-transfers', [
            'product_id' => $product->id,
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'quantity' => 4,
        ])->assertCreated();

        $this->assertDatabaseHas('stocks', ['warehouse_id' => $from->id, 'quantity' => 6]);
        $this->assertDatabaseHas('stocks', ['warehouse_id' => $to->id, 'quantity' => 4]);

        $legs = StockMovement::where('product_id', $product->id)
            ->whereIn('type', ['transfer_out', 'transfer_in'])
            ->get();

        $this->assertCount(2, $legs);
        $this->assertCount(1, $legs->pluck('reference')->unique());
        $this->assertSame(4, $legs->firstWhere('type', 'transfer_in')->quantity);
        $this->assertSame(-4, $legs->firstWhere('type', 'transfer_out')->quantity);
    }

    public function test_a_transfer_with_insufficient_stock_is_rejected_and_rolled_back(): void
    {
        $product = $this->makeProduct();
        $from = $this->makeWarehouse();
        $to = $this->makeWarehouse();

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $from->id,
            'type' => 'in',
            'quantity' => 2,
        ])->assertCreated();

        $this->apiPost('/api/stock-transfers', [
            'product_id' => $product->id,
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'quantity' => 5,
        ])->assertStatus(422);

        // nothing moved
        $this->assertDatabaseHas('stocks', ['warehouse_id' => $from->id, 'quantity' => 2]);
        $this->assertDatabaseMissing('stocks', ['warehouse_id' => $to->id]);
    }

    public function test_a_transfer_to_the_same_warehouse_is_rejected(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $this->apiPost('/api/stock-transfers', [
            'product_id' => $product->id,
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id' => $warehouse->id,
            'quantity' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('to_warehouse_id');
    }
}
