<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->using(Membership::class)
            ->withPivot(['role', 'data_scope', 'department_id', 'position_id'])
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function menuPermissions(): HasMany
    {
        return $this->hasMany(MenuPermission::class);
    }

    public function context(): \App\Support\UserContext
    {
        return \App\Support\UserContext::for($this);
    }

    public function menuKeys(): array
    {
        if ($this->relationLoaded('menuPermissions')) {
            return $this->menuPermissions->pluck('menu_key')->all();
        }

        return $this->menuPermissions()->pluck('menu_key')->all();
    }

    public function hasMenuAccess(string $menu): bool
    {
        $item = config('menu.items.'.$menu);

        if (! $item) {
            return false;
        }

        if ($this->is_superadmin) {
            return true;
        }

        if ($item['always'] ?? false) {
            return true;
        }

        if ($item['system_only'] ?? false) {
            return false;
        }

        return in_array($menu, $this->menuKeys(), true);
    }
}
