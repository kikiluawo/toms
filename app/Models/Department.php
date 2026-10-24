<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Departments usable by a company: its own plus the group-wide ones.
     *
     * @return Builder<self>
     */
    public static function usableFor(?int $companyId): Builder
    {
        return static::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('company_id')->when($companyId, fn ($q) => $q->orWhere('company_id', $companyId)));
    }
}
