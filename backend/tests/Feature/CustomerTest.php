<?php

namespace Tests\Feature;

use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_a_customer_can_be_created(): void
    {
        $response = $this->apiPost('/api/customers', [
            'name' => 'Alice Johnson',
            'company_name' => 'Acme Corp',
            'email' => 'alice@acme.test',
            'phone' => '123-456-7890',
            'address' => '123 Market St',
            'tax_number' => 'TAX-9988',
            'is_active' => true,
        ])->assertCreated();

        $response->assertJsonPath('data.name', 'Alice Johnson')
            ->assertJsonPath('data.company_name', 'Acme Corp')
            ->assertJsonPath('data.email', 'alice@acme.test');

        $this->assertDatabaseHas('customers', [
            'name' => 'Alice Johnson',
            'company_name' => 'Acme Corp',
        ]);
    }

    public function test_customers_can_be_searched_and_filtered(): void
    {
        $this->makeCustomer(['name' => 'John Wayne', 'company_name' => 'Western Inc', 'is_active' => true]);
        $this->makeCustomer(['name' => 'Sarah Connor', 'company_name' => 'Cyberdyne', 'is_active' => false]);

        $search = $this->apiGet('/api/customers?search=Wayne')->assertOk();
        $this->assertCount(1, $search->json('data'));
        $this->assertSame('John Wayne', $search->json('data.0.name'));

        $active = $this->apiGet('/api/customers?is_active=1')->assertOk();
        $this->assertCount(1, $active->json('data'));
        $this->assertSame('John Wayne', $active->json('data.0.name'));
    }

    public function test_a_customer_can_be_updated(): void
    {
        $customer = $this->makeCustomer(['name' => 'Old Name']);

        $this->apiPut("/api/customers/{$customer->id}", [
            'name' => 'New Name',
            'company_name' => 'New Company',
        ])->assertOk()->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
            'company_name' => 'New Company',
        ]);
    }

    public function test_a_customer_cannot_be_deleted_if_it_has_sales_orders(): void
    {
        $customer = $this->makeCustomer();
        SalesOrder::create([
            'customer_id' => $customer->id,
            'number' => 'SO-00001',
            'status' => 'draft',
            'total' => 100,
        ]);

        $this->apiDelete("/api/customers/{$customer->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete a customer that has sales orders.');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_a_customer_can_be_deleted_when_it_has_no_orders(): void
    {
        $customer = $this->makeCustomer();

        $this->apiDelete("/api/customers/{$customer->id}")->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
