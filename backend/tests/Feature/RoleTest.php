<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    public function test_me_reports_the_users_role(): void
    {
        $this->authenticate('manager');

        $this->apiGet('/api/me')->assertOk()->assertJsonPath('role', 'manager');
    }

    public function test_staff_cannot_manage_inventory(): void
    {
        $this->authenticate('staff');

        $this->apiPost('/api/products', ['name' => 'Nope', 'unit' => 'pcs'])->assertStatus(403);
        $this->apiPost('/api/categories', ['name' => 'Nope'])->assertStatus(403);
        $this->apiPost('/api/warehouses', ['name' => 'Nope'])->assertStatus(403);
        $this->apiPost('/api/suppliers', ['name' => 'Nope'])->assertStatus(403);
    }

    public function test_staff_can_still_do_daily_operations(): void
    {
        $this->authenticate('staff');

        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 5,
        ])->assertCreated();

        $this->apiGet('/api/products')->assertOk();
    }

    public function test_manager_can_manage_inventory_but_not_users(): void
    {
        $this->authenticate('manager');

        $this->apiPost('/api/products', ['name' => 'Managed', 'unit' => 'pcs'])->assertCreated();

        $this->apiGet('/api/users')->assertStatus(403);
    }
}
