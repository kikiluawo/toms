<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'unit',
        'qty_ordered',
        'qty_received',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered' => 'decimal:3',
            'qty_received' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receiptLines(): HasMany
    {
        return $this->hasMany(PurchaseReceiptLine::class);
    }

    /** Volume that the supplier still owes on this line. */
    public function outstandingQty(): float
    {
        return max(0, (float) $this->qty_ordered - (float) $this->qty_received);
    }

    public function isFullyReceived(): bool
    {
        return $this->outstandingQty() <= 0.0005;
    }
}
