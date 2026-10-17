<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Mail\PurchaseOrderReceived;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiToken;
use Tests\Concerns\CreatesModels;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use AuthenticatesApiToken, CreatesModels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    private function moveStock($product, $warehouse, int $quantity, string $type = 'in')
    {
        return $this->apiPost('/api/stock-movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => $type,
            'quantity' => $quantity,
        ]);
    }

    public function test_a_movement_that_crosses_the_reorder_level_emails_opted_in_users(): void
    {
        $product = $this->makeProduct(['reorder_level' => 5]);
        $warehouse = $this->makeWarehouse();

        $this->moveStock($product, $warehouse, 10)->assertCreated();

        $this->apiUser->update(['notify_low_stock' => true]);
        Mail::fake();

        $this->moveStock($product, $warehouse, 6, 'out')->assertCreated();

        Mail::assertSent(LowStockAlert::class, function (LowStockAlert $mail) use ($product) {
            return $mail->product->id === $product->id
                && (int) $mail->quantity === 4
                && $mail->hasTo($this->apiUser->email);
        });
    }

    public function test_users_who_have_not_opted_in_are_not_emailed(): void
    {
        $product = $this->makeProduct(['reorder_level' => 5]);
        $warehouse = $this->makeWarehouse();

        $this->moveStock($product, $warehouse, 10)->assertCreated();
        Mail::fake();

        $this->moveStock($product, $warehouse, 6, 'out')->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_a_movement_that_stays_above_the_reorder_level_is_silent(): void
    {
        $product = $this->makeProduct(['reorder_level' => 5]);
        $warehouse = $this->makeWarehouse();

        $this->moveStock($product, $warehouse, 20)->assertCreated();

        $this->apiUser->update(['notify_low_stock' => true]);
        Mail::fake();

        $this->moveStock($product, $warehouse, 3, 'out')->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_a_product_without_a_reorder_level_never_alerts(): void
    {
        $product = $this->makeProduct(['reorder_level' => 0]);
        $warehouse = $this->makeWarehouse();

        $this->moveStock($product, $warehouse, 10)->assertCreated();

        $this->apiUser->update(['notify_low_stock' => true]);
        Mail::fake();

        $this->moveStock($product, $warehouse, 10, 'out')->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_a_fully_received_purchase_order_emails_opted_in_users(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $this->makeSupplier()->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 4]],
        ])->assertCreated()->json('data');

        $this->apiUser->update(['notify_purchase_orders' => true]);
        Mail::fake();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
        ])->assertOk()->assertJsonPath('data.status', 'received');

        Mail::assertSent(PurchaseOrderReceived::class, function (PurchaseOrderReceived $mail) use ($order) {
            return $mail->purchaseOrder->id === $order['id']
                && $mail->hasTo($this->apiUser->email);
        });
    }

    public function test_a_partial_receive_does_not_email(): void
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $this->makeSupplier()->id,
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 1]],
        ])->assertCreated()->json('data');

        $this->apiUser->update(['notify_purchase_orders' => true]);
        Mail::fake();

        $this->apiPost("/api/purchase-orders/{$order['id']}/receive", [
            'warehouse_id' => $warehouse->id,
            'items' => [['id' => $order['items'][0]['id'], 'quantity' => 4]],
        ])->assertOk()->assertJsonPath('data.status', 'partially_received');

        Mail::assertNothingSent();
    }

    public function test_a_user_can_update_their_own_notification_preferences(): void
    {
        $response = $this->apiPut('/api/me/preferences', [
            'notify_low_stock' => true,
            'notify_purchase_orders' => true,
        ])->assertOk();

        $this->assertTrue($response->json('data.notify_low_stock'));
        $this->assertTrue($response->json('data.notify_purchase_orders'));
        $this->assertTrue($this->apiUser->fresh()->notify_low_stock);
    }

    public function test_a_staff_member_can_manage_their_own_preferences(): void
    {
        $this->authenticate('staff');

        $this->apiPut('/api/me/preferences', ['notify_low_stock' => true])->assertOk();

        $this->assertTrue($this->apiUser->fresh()->notify_low_stock);
    }

    public function test_the_notification_emails_render(): void
    {
        $product = $this->makeProduct(['reorder_level' => 5]);
        $warehouse = $this->makeWarehouse();

        $this->assertStringContainsString(
            $product->name,
            (new LowStockAlert($product, $warehouse, 4))->render(),
        );

        $order = $this->apiPost('/api/purchase-orders', [
            'supplier_id' => $this->makeSupplier()->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 4]],
        ])->assertCreated()->json('data');

        $purchaseOrder = PurchaseOrder::with(['supplier', 'items.product'])->find($order['id']);

        $this->assertStringContainsString(
            $order['number'],
            (new PurchaseOrderReceived($purchaseOrder))->render(),
        );
    }
}
