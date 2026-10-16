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

        return DB::transaction(function () use ($product, $warehouse, $type, $delta, $note, $reference, $user) {
            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['quantity' => 0],
            );

            $stock = Stock::whereKey($stock->id)->lockForUpdate()->first();

            $newQuantity = $stock->quantity + $delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock: only {$stock->quantity} available.",
                ]);
            }

            $stock->update(['quantity' => $newQuantity]);

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
}
