<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const IN = 'in';

    public const OUT = 'out';

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_id',
        'direction',
        'qty',
        'ref_type',
        'ref_id',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Stock on hand per product per location. Inbound and outbound live in one ledger,
     * so the balance is always the sum and can never be edited directly.
     *
     * @return Builder<self>
     */
    public static function balanceQuery(): Builder
    {
        return static::query()
            ->selectRaw('product_id, branch_id, company_id')
            ->selectRaw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) as balance")
            ->groupBy('product_id', 'branch_id', 'company_id');
    }
}
