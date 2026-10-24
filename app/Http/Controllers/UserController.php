<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Membership;
use App\Models\Position;
use App\Models\User;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $context = UserContext::for($actor);
        $companyIds = $context->visibleCompanyIds();

        $query = User::query()->with(['companies', 'branches', 'menuPermissions'])->orderBy('name');

        if (! $context->isSystem()) {
            $query->where(function ($scoped) use ($companyIds, $actor) {
                $scoped->whereIn('id', function ($sub) use ($companyIds) {
                    $sub->select('user_id')->from('company_user')->whereIn('company_id', $companyIds);
                })->orWhere('id', $actor->id);
            });
        }

        $users = $query->paginate(12);

        return view('users.index', [
            'users' => $users,
            'membershipMap' => Membership::query()
                ->with(['department', 'position'])
                ->whereIn('user_id', $users->pluck('id'))
                ->get()
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->keyBy('company_id')),
            'menuItems' => $this->menuItems(),
        ]);
    }

    public function create(Request $request): View
    {
        $companies = $this->manageableCompanies($request);

        return view('users.create', [
            'companies' => $companies,
            'roleOptions' => $this->assignableRoles($request),
            'dictionaries' => $this->dictionaries($companies),
            'menuItems' => $this->menuItems(),
            'selectedCompanies' => [],
            'selectedBranches' => [],
            'selectedMenus' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);
        [$companyStates, $branchIds, $menuKeys] = $this->resolveAccess($request, $data);

        DB::transaction(function () use ($data, $companyStates, $branchIds, $menuKeys) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $this->syncAccess($user, $companyStates, $branchIds, $menuKeys);
        });

        return redirect()->route('users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureManageable($request, $user);

        $user->load(['companies', 'branches', 'menuPermissions']);

        $companies = $this->manageableCompanies($request);

        return view('users.edit', [
            'user' => $user,
            'companies' => $companies,
            'roleOptions' => $this->assignableRoles($request),
            'dictionaries' => $this->dictionaries($companies),
            'menuItems' => $this->menuItems(),
            'selectedCompanies' => $user->companies
                ->mapWithKeys(fn ($company) => [
                    $company->id => [
                        'role' => $company->pivot->role,
                        'data_scope' => $company->pivot->data_scope,
                        'department_id' => $company->pivot->department_id,
                        'position_id' => $company->pivot->position_id,
                    ],
                ])
                ->all(),
            'selectedBranches' => $user->branches->pluck('id')->all(),
            'selectedMenus' => $user->menuPermissions->pluck('menu_key')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureManageable($request, $user);

        $data = $this->validateUser($request, $user);
        [$companyStates, $branchIds, $menuKeys] = $this->resolveAccess($request, $data);

        if ($user->is($request->user()) && ! in_array('users', $menuKeys, true)) {
            throw ValidationException::withMessages([
                'menus' => 'Anda tidak dapat mencabut akses menu Users dari akun sendiri.',
            ]);
        }

        DB::transaction(function () use ($user, $data, $companyStates, $branchIds, $menuKeys) {
            $user->fill([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            $this->syncAccess($user, $companyStates, $branchIds, $menuKeys);
        });

        return redirect()->route('users.index')->with('status', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureManageable($request, $user);

        if ($user->is($request->user())) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        DB::transaction(function () use ($user) {
            $user->companies()->detach();
            $user->branches()->detach();
            $user->menuPermissions()->delete();
            $user->delete();
        });

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'max:255',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'companies' => ['nullable', 'array'],
            'companies.*.selected' => ['nullable'],
            'companies.*.role' => ['nullable', 'string', Rule::in(array_keys(config('user.roles')))],
            'companies.*.department_id' => ['nullable', 'integer'],
            'companies.*.position_id' => ['nullable', 'integer'],
            'branches' => ['nullable', 'array'],
            'branches.*' => ['integer'],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['string', Rule::in($this->configurableKeys())],
        ]);
    }

    /**
     * @return array{0: array<int, array<string, string|int|null>>, 1: array<int, int>, 2: array<int, string>}
     */
    private function resolveAccess(Request $request, array $data): array
    {
        $manageable = $this->manageableCompanies($request);
        $assignable = array_keys($this->assignableRoles($request));

        $selected = collect($request->input('companies', []))
            ->filter(fn ($row) => ! empty($row['selected']))
            ->mapWithKeys(function ($row, $companyId) use ($manageable, $assignable) {
                $company = $manageable->get((int) $companyId);

                if (! $company) {
                    return [];
                }

                $role = $row['role'] ?? null;

                if (! $role) {
                    throw ValidationException::withMessages([
                        'companies.'.$company->id.'.role' => 'Role wajib dipilih untuk '.$company->name.'.',
                    ]);
                }

                if (! in_array($role, $assignable, true)) {
                    throw ValidationException::withMessages([
                        'companies.'.$company->id.'.role' => 'Role '.(config("user.roles.$role.label")).' di luar wewenang Anda untuk diberikan.',
                    ]);
                }

                return [
                    $company->id => [
                        'role' => $role,
                        'data_scope' => config('user.roles.'.$role.'.scope'),
                        'department_id' => $this->dictionaryId(Department::class, $row['department_id'] ?? null, $company, 'department'),
                        'position_id' => $this->dictionaryId(Position::class, $row['position_id'] ?? null, $company, 'position'),
                    ],
                ];
            });

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages([
                'companies' => 'Pilih minimal satu PT untuk user ini.',
            ]);
        }

        $branchPool = $manageable->whereIn('id', $selected->keys())->flatMap->branches->keyBy('id');

        $branchIds = collect($request->input('branches', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $branchPool->has($id))
            ->unique()
            ->values();

        foreach ($selected as $companyId => $state) {
            $company = $manageable->get($companyId);

            // A branch role only needs a location when the PT actually has locations to
            // choose from; a PT without one is worked as a whole.
            if ($state['data_scope'] !== 'branch' || $company->branches->isEmpty()) {
                continue;
            }

            $hasBranch = $branchIds->contains(
                fn ($branchId) => $branchPool[$branchId]->company_id === $companyId
            );

            if (! $hasBranch) {
                throw ValidationException::withMessages([
                    'branches' => 'Pilih minimal satu lokasi untuk '.$company->name.'.',
                ]);
            }
        }

        $branchIds = $branchIds
            ->filter(fn ($branchId) => $selected[$branchPool[$branchId]->company_id]['data_scope'] === 'branch')
            ->values()
            ->all();

        $menuKeys = collect($data['menus'] ?? [])
            ->intersect($this->configurableKeys())
            ->values()
            ->all();

        return [$selected->all(), $branchIds, $menuKeys];
    }

    /**
     * @param  array<int, array<string, string|int|null>>  $companyStates
     * @param  array<int, int>  $branchIds
     * @param  array<int, string>  $menuKeys
     */
    private function syncAccess(User $user, array $companyStates, array $branchIds, array $menuKeys): void
    {
        $user->companies()->sync($companyStates);
        $user->branches()->sync($branchIds);

        $user->menuPermissions()->delete();
        $user->menuPermissions()->createMany(
            collect($menuKeys)->unique()->map(fn ($key) => ['menu_key' => $key])->all()
        );
    }

    /**
     * Department and position lists follow the company, so an id copied from another
     * PT would attach the wrong label.
     */
    private function dictionaryId(string $model, int|string|null $id, Company $company, string $field): ?int
    {
        if (! is_numeric($id)) {
            return null;
        }

        if (! $model::usableFor($company->id)->whereKey((int) $id)->exists()) {
            throw ValidationException::withMessages([
                'companies.'.$company->id.'.'.$field => 'Pilihan '.$field.' tidak terdaftar di '.$company->name.'.',
            ]);
        }

        return (int) $id;
    }

    private function manageableCompanies(Request $request)
    {
        return UserContext::for($request->user())->visibleCompanies();
    }

    /**
     * Per-company department and position options: group-wide entries plus that
     * company's own, so the lists follow the PT as the org chart requires.
     */
    private function dictionaries($companies): array
    {
        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();
        $positions = Position::query()->where('is_active', true)->orderBy('name')->get();

        $pick = function ($rows, int $companyId) {
            return $rows->whereNull('company_id')
                ->concat($rows->where('company_id', $companyId))
                ->values();
        };

        return $companies
            ->mapWithKeys(fn ($company) => [
                $company->id => [
                    'departments' => $pick($departments, $company->id),
                    'positions' => $pick($positions, $company->id),
                ],
            ])
            ->all();
    }

    private function menuItems()
    {
        return collect(config('menu.items'))
            ->reject(fn ($item) => ($item['always'] ?? false) || ($item['system_only'] ?? false));
    }

    /**
     * @return array<int, string>
     */
    private function configurableKeys(): array
    {
        return $this->menuItems()->keys()->all();
    }

    private function ensureManageable(Request $request, User $user): void
    {
        abort_unless(
            UserContext::for($request->user())->canManage($user),
            403,
            'Anda tidak bisa mengelola user dengan jangkauan lebih tinggi.'
        );
    }

    /**
     * Nobody may hand out a role that reaches further than they do, otherwise a PT
     * manager could promote someone to the group level and unlock every company.
     */
    private function assignableRoles(Request $request): array
    {
        $rank = UserContext::rankOf(UserContext::for($request->user())->dataScope());

        return collect(config('user.roles'))
            ->filter(fn ($role) => UserContext::rankOf($role['scope']) <= $rank)
            ->all();
    }
}
