<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * Apply a signed delta to a product/warehouse level and record the movement.
     *
     * Negative results are rejected. The stock row is locked for the duration
     * of the transaction so concurrent movements can't interleave.
     */
    public function record(
        Product $product,
        Warehouse $warehouse,
        string $type,
        int $delta,
        ?string $note = null,
        ?string $reference = null,
        ?User $user = null,
    ): StockMovement {
        if ($delta === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be a non-zero amount.',
            ]);
        }

        $before = 0;
        $after = 0;

        $movement = DB::transaction(function () use ($product, $warehouse, $type, $delta, $note, $reference, $user, &$before, &$after) {
            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['quantity' => 0],
            );

            $stock = Stock::whereKey($stock->id)->lockForUpdate()->first();

            $before = $stock->quantity;
            $newQuantity = $stock->quantity + $delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock: only {$stock->quantity} available.",
                ]);
            }

            $stock->update(['quantity' => $newQuantity]);

            $after = $newQuantity;

            return StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $delta,
                'balance_after' => $newQuantity,
                'reference' => $reference,
                'note' => $note,
            ]);
        });

        $this->notifyIfLow($product, $warehouse, $before, $after);

        return $movement;
    }

    /**
     * Move stock between two warehouses as two linked movements.
     */
    public function transfer(
        Product $product,
        Warehouse $from,
        Warehouse $to,
        int $quantity,
        ?string $note = null,
        ?User $user = null,
    ): void {
        $reference = (string) Str::uuid();

        DB::transaction(function () use ($product, $from, $to, $quantity, $note, $reference, $user) {
            $this->record($product, $from, 'transfer_out', -$quantity, $note, $reference, $user);
            $this->record($product, $to, 'transfer_in', $quantity, $note, $reference, $user);
        });
    }

    /**
     * Alert opted-in users when a movement crosses the reorder level downwards.
     */
    private function notifyIfLow(Product $product, Warehouse $warehouse, int $before, int $after): void
    {
        $level = (int) $product->reorder_level;

        if ($level > 0 && $before > $level && $after <= $level) {
            $this->notifications->lowStock($product, $warehouse, $after);
        }
    }
}
