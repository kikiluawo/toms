<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Membership;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Single source of truth for what an authenticated user may see and write.
 */
class UserContext
{
    public const ALL = 'all';

    private ?Collection $pivots = null;

    private ?Collection $companies = null;

    private ?array $assignedBranchIds = null;

    private array $dictionary = [];

    public function __construct(
        private readonly User $user,
        private readonly int|string|null $activeCompanyId,
        private readonly int|string|null $activeBranchId,
    ) {}

    public static function for(User $user): static
    {
        return new static($user, session('active_company_id'), session('active_branch_id'));
    }

    public function user(): User
    {
        return $this->user;
    }

    /** @return Collection<int, Membership> keyed by company id */
    public function memberships(): Collection
    {
        return $this->pivots ??= Membership::query()
            ->where('user_id', $this->user->id)
            ->get()
            ->keyBy('company_id');
    }

    /**
     * Companies the user may open. A group-scope member sees every active company,
     * whether or not they hold a membership row there.
     *
     * @return Collection<int, Company>
     */
    public function visibleCompanies(): Collection
    {
        if ($this->companies) {
            return $this->companies;
        }

        $query = Company::query()->where('is_active', true)->with('branches')->orderBy('name');

        if (! $this->isGroup()) {
            $query->whereIn('id', $this->memberships()->keys());
        }

        return $this->companies = $query->get()->keyBy('id');
    }

    /** @return array<int, int> */
    public function visibleCompanyIds(): array
    {
        return array_map(intval(...), $this->visibleCompanies()->keys()->all());
    }

    public function dataScope(): string
    {
        if ($this->user->is_superadmin) {
            return 'system';
        }

        $scopes = $this->memberships()->pluck('data_scope');

        return match (true) {
            $scopes->contains('group') => 'group',
            $scopes->contains('company') => 'company',
            $scopes->isNotEmpty() => 'branch',
            default => 'none',
        };
    }

    /**
     * System reach sits above the group level and needs no membership row at all:
     * it is what keeps the installer account usable before any PT exists.
     */
    public function isSystem(): bool
    {
        return $this->dataScope() === 'system';
    }

    public function isGroup(): bool
    {
        return in_array($this->dataScope(), ['system', 'group'], true);
    }

    private const RANK = ['none' => 0, 'branch' => 1, 'company' => 2, 'group' => 3, 'system' => 4];

    public static function rankOf(string $scope): int
    {
        return self::RANK[$scope] ?? 0;
    }

    /** May the actor administrate the target account? */
    public function canManage(User $target): bool
    {
        if ($target->is($this->user)) {
            return true;
        }

        $actor = self::rankOf($this->dataScope());
        $targetRank = self::rankOf(UserContext::for($target)->dataScope());

        if ($targetRank > $actor) {
            return false;
        }

        if ($this->isSystem()) {
            return true;
        }

        return $targetRank > 0 && $target->companies()
            ->whereIn('companies.id', $this->visibleCompanyIds())
            ->exists();
    }

    public function membershipFor(int $companyId): ?Membership
    {
        return $this->memberships()->get($companyId);
    }

    public function scopeAt(int $companyId): string
    {
        if ($this->isSystem()) {
            return 'system';
        }

        return $this->isGroup()
            ? 'group'
            : (string) ($this->membershipFor($companyId)?->data_scope ?? 'none');
    }

    public function isAllCompanies(): bool
    {
        return $this->isGroup() && $this->activeCompanyId === self::ALL;
    }

    /** True when the session points at a company (or the group-wide view) the user may use. */
    public function isGrounded(): bool
    {
        return $this->isAllCompanies() || $this->company() !== null;
    }

    public function company(): ?Company
    {
        if ($this->isAllCompanies() || ! is_numeric($this->activeCompanyId)) {
            return null;
        }

        return $this->visibleCompanies()->get((int) $this->activeCompanyId);
    }

    public function branch(): ?Branch
    {
        $company = $this->company();

        if (! $company || ! is_numeric($this->activeBranchId)) {
            return null;
        }

        $branchId = (int) $this->activeBranchId;

        return in_array($branchId, $this->visibleBranchIds($company->id), true)
            ? $company->branches->firstWhere('id', $branchId)
            : null;
    }

    /** @return array<int, int> branch ids the user may open inside the given company */
    public function visibleBranchIds(int $companyId): array
    {
        $company = $this->visibleCompanies()->get($companyId);

        if (! $company) {
            return [];
        }

        $branchIds = $company->branches->pluck('id')->map(intval(...));

        if ($this->scopeAt($companyId) === 'branch') {
            $branchIds = $branchIds->intersect($this->assignedBranches());
        }

        return $branchIds->values()->all();
    }

    public function canViewCompany(int|string|null $companyId): bool
    {
        if ($companyId === self::ALL) {
            return $this->isGroup();
        }

        return is_numeric($companyId)
            && in_array((int) $companyId, $this->visibleCompanyIds(), true);
    }

    /**
     * Write permission. Branch-scope members must always name one of their own
     * branches; company and group members may act at company level.
     */
    public function canActOn(int|string|null $companyId, ?int $branchId = null): bool
    {
        if (! $this->canViewCompany($companyId)) {
            return false;
        }

        if ($companyId === self::ALL) {
            return $branchId === null;
        }

        $companyId = (int) $companyId;

        if ($branchId === null) {
            // A branch-scope member at a PT that has no locations yet works at the PT
            // itself: there is no smaller place to point them at.
            return $this->scopeAt($companyId) !== 'branch'
                || $this->visibleCompanies()->get($companyId)?->branches->isEmpty();
        }

        return in_array($branchId, $this->visibleBranchIds($companyId), true);
    }

    /** Companies this user may create records for, addressed by id. */
    public function orderableCompanies(): Collection
    {
        return $this->visibleCompanies()->filter(
            fn (Company $company) => $this->canActOn($company->id) || $this->visibleBranchIds($company->id) !== []
        );
    }

    public function roleAt(?int $companyId = null): ?string
    {
        $companyId ??= $this->company()?->id;

        if ($companyId === null) {
            return $this->memberships()->firstWhere('data_scope', 'group')?->role;
        }

        return $this->membershipFor($companyId)?->role;
    }

    /**
     * The role that really applies to a company. A group-scope director is not a member
     * of every PT he commands, so his anchor role is what governs those PTs too.
     */
    public function roleFor(int $companyId): ?string
    {
        if ($this->isSystem()) {
            return 'superadmin';
        }

        return $this->membershipFor($companyId)?->role
            ?? $this->memberships()->firstWhere('data_scope', 'group')?->role;
    }

    public function canOrderFor(int $companyId): bool
    {
        if ($this->isSystem()) {
            return $this->canViewCompany($companyId);
        }

        return $this->canActOn($companyId)
            && in_array($this->roleFor($companyId), config('oil.order_roles'), true);
    }

    /** Companies this user may raise an oil order for, addressed by id. */
    public function purchasableCompanies(): Collection
    {
        return $this->visibleCompanies()->filter(
            fn (Company $company) => $this->canOrderFor($company->id)
        );
    }

    public function roleLabel(?int $companyId = null): ?string
    {
        $role = $this->roleAt($companyId);

        if ($role) {
            return config('user.roles.'.$role.'.label');
        }

        return $this->isSystem() ? 'SUPERADMIN' : null;
    }

    public function scopeLabel(?int $companyId = null): string
    {
        return config('user.scopes.'.$this->scopeAt($companyId ?? $this->company()?->id ?? 0))
            ?? 'Tidak ada akses';
    }

    public function departmentName(?int $companyId = null): ?string
    {
        return $this->dictionaryValue(Department::class, 'department_id', $companyId);
    }

    public function positionName(?int $companyId = null): ?string
    {
        return $this->dictionaryValue(Position::class, 'position_id', $companyId);
    }

    /** @return array<int, int> */
    private function assignedBranches(): array
    {
        return $this->assignedBranchIds ??= $this->user->branches()
            ->pluck('branches.id')
            ->map(intval(...))
            ->all();
    }

    private function dictionaryValue(string $model, string $column, ?int $companyId): ?string
    {
        $companyId ??= $this->company()?->id;

        $membership = $companyId ? $this->membershipFor($companyId) : null;
        $key = $membership?->{$column};

        if (! $key) {
            return null;
        }

        if (! isset($this->dictionary[$model])) {
            $this->dictionary[$model] = $model::query()->pluck('name', 'id')->all();
        }

        return $this->dictionary[$model][$key] ?? null;
    }
}
