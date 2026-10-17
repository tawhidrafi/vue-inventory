<?php

namespace App\Services;

use App\Mail\LowStockAlert;
use App\Mail\PurchaseOrderReceived;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Alert every opted-in user that a product dropped to its reorder level.
     *
     * The mail is only sent once the surrounding transaction has committed, so
     * a rolled-back movement never triggers a notification.
     */
    public function lowStock(Product $product, Warehouse $warehouse, int $quantity): void
    {
        $this->defer(fn () => $this->send(
            'notify_low_stock',
            fn () => new LowStockAlert($product->fresh(), $warehouse, $quantity),
        ));
    }

    /**
     * Tell every opted-in user that a purchase order has been fully received.
     */
    public function purchaseOrderReceived(PurchaseOrder $purchaseOrder): void
    {
        $this->defer(fn () => $this->send(
            'notify_purchase_orders',
            fn () => new PurchaseOrderReceived($purchaseOrder),
        ));
    }

    /**
     * Run the callback after the active transaction commits (immediately when
     * there is no transaction).
     */
    private function defer(callable $callback): void
    {
        DB::afterCommit($callback);
    }

    /**
     * @param  callable(): Mailable  $mailable
     */
    private function send(string $preference, callable $mailable): void
    {
        try {
            User::query()
                ->where($preference, true)
                ->get()
                ->each(fn (User $user) => Mail::to($user)->send($mailable()));
        } catch (\Throwable $e) {
            // a broken mailer must never fail the operation that triggered it
            report($e);
        }
    }
}
