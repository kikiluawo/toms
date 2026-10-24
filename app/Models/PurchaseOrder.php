<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const DRAFT = 'draft';

    public const SUBMITTED = 'submitted';

    public const COMPLETED = 'completed';

    protected $fillable = [
        'po_number',
        'company_id',
        'branch_id',
        'supplier_id',
        'order_date',
        'expected_date',
        'supplier_ref',
        'notes',
        'status',
        'created_by',
        'submitted_by',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('sort_order');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    /** Only a submitted order may be received; a completed one is fully in. */
    public function isOpenForReceipt(): bool
    {
        return $this->status === self::SUBMITTED;
    }

    public function totalQty(): float
    {
        return (float) $this->lines->sum('qty_ordered');
    }

    public function totalAmount(): float
    {
        return (float) $this->lines->sum('line_total');
    }

    public static function statusLabel(string $status): string
    {
        return config('oil.po_status.'.$status, $status);
    }
}
