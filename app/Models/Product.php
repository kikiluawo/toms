<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * The catalogue every PT may order from. Retired items keep their history but stop
     * showing up in new documents.
     *
     * @return Builder<self>
     */
    public static function active(): Builder
    {
        return static::query()->where('is_active', true);
    }

    public function getUnitLabelAttribute(): string
    {
        return config('oil.units.'.$this->unit, $this->unit);
    }

    public function getCategoryLabelAttribute(): string
    {
        return config('oil.categories.'.$this->category, $this->category);
    }
}
