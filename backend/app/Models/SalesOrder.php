<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'number',
        'status',
        'order_date',
        'expected_ship_date',
        'shipped_at',
        'shipping_address',
        'notes',
        'created_by',
        'subtotal',
        'tax',
        'discount',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_ship_date' => 'date',
            'shipped_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
