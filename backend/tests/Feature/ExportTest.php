<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_products_can_be_exported_as_csv(): void
    {
        $this->makeProduct(['sku' => 'EXP-1', 'name' => 'Export Me']);

        $response = $this->get('/api/exports/products', $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('SKU,Name', $csv);
        $this->assertStringContainsString('EXP-1', $csv);
        $this->assertStringContainsString('Export Me', $csv);
    }

    public function test_stock_movements_can_be_exported_as_csv(): void
    {
        $product = $this->makeProduct(['name' => 'Widget']);
        $warehouse = $this->makeWarehouse(['name' => 'Main']);

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 5,
        ])->assertCreated();

        $response = $this->get('/api/exports/stock-movements', $this->authHeaders());

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Widget', $csv);
        $this->assertStringContainsString('Main', $csv);
    }
}
