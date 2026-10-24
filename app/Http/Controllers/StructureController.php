<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Membership;
use App\Models\Position;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StructureController extends Controller
{
    public function index(Request $request): View
    {
        $context = UserContext::for($request->user());
        $companies = $context->visibleCompanies();

        return view('structure.index', [
            'companies' => $companies,
            'userContext' => $context,
            'departments' => $this->grouped(Department::query(), $companies),
            'positions' => $this->grouped(Position::query()->with('department'), $companies),
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $this->validateTarget($request, [
            'name' => ['required', 'string', 'max:60'],
        ]);

        $this->ensureUnique(
            Department::query()->where('company_id', $data['company_id'])
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists(),
            $data['company_id'],
            'department',
            'Department'
        );

        Department::create([
            'company_id' => $data['company_id'],
            'name' => mb_strtoupper($data['name']),
        ]);

        return back()->with('status', 'Department berhasil ditambahkan.');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $data = $this->validateTarget($request, [
            'name' => ['required', 'string', 'max:80'],
            'department_id' => ['nullable', 'integer'],
        ]);

        $this->ensureUnique(
            Position::query()->where('company_id', $data['company_id'])
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists(),
            $data['company_id'],
            'position',
            'Posisi'
        );

        $departmentId = $data['department_id'] ?? null;

        if ($departmentId !== null) {
            $department = Department::usableFor($data['company_id'])->find($departmentId);
            if (! $department) {
                throw ValidationException::withMessages([
                    $this->errorKey('position', $data['company_id'], 'department_id')
                        => 'Department ini tidak tersedia di PT tersebut.',
                ]);
            }
        }

        Position::create([
            'company_id' => $data['company_id'],
            'name' => $data['name'],
            'department_id' => $departmentId,
        ]);

        return back()->with('status', 'Posisi berhasil ditambahkan.');
    }

    public function destroyDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->ensureCompanyEditable($request, $department->company_id);

        if (Membership::query()->where('department_id', $department->id)->exists()) {
            return back()->with('error', 'Department ini masih dipakai user. Lepaskan dulu dari form user.');
        }

        $department->delete();

        return back()->with('status', 'Department berhasil dihapus.');
    }

    public function destroyPosition(Request $request, Position $position): RedirectResponse
    {
        $this->ensureCompanyEditable($request, $position->company_id);

        if (Membership::query()->where('position_id', $position->id)->exists()) {
            return back()->with('error', 'Posisi ini masih dipakai user. Lepaskan dulu dari form user.');
        }

        $position->delete();

        return back()->with('status', 'Posisi berhasil dihapus.');
    }

    private function grouped($query, Collection $companies): Collection
    {
        return $query
            ->where(fn ($q) => $q->whereIn('company_id', $companies->keys())->orWhereNull('company_id'))
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($item) => $item->company_id ?? 'group');
    }

    private function validateTarget(Request $request, array $rules): array
    {
        $data = $request->validate(array_merge([
            'company_id' => ['nullable', 'integer'],
        ], $rules));

        $companyId = $data['company_id'] === null ? null : (int) $data['company_id'];

        if ($companyId !== null) {
            $this->ensureCompanyEditable($request, $companyId);
        } elseif (! UserContext::for($request->user())->isGroup()) {
            abort(403, 'Hanya user level grup yang bisa mengelola kamus bersama.');
        }

        return array_merge($data, ['company_id' => $companyId]);
    }

    /**
     * Group members may edit every active company; others only the companies they own.
     * This asks for master-data authority, not for ordering rights: a branch member who
     * happens to sit at a PT without locations may still not rewrite that PT's dictionary.
     */
    private function ensureCompanyEditable(Request $request, ?int $companyId): void
    {
        $context = UserContext::for($request->user());

        abort_unless(
            $companyId !== null && $context->canViewCompany($companyId) && $context->scopeAt($companyId) !== 'branch',
            403
        );
    }

    private function ensureUnique(bool $exists, ?int $companyId, string $group, string $label): void
    {
        if ($exists) {
            throw ValidationException::withMessages([
                $this->errorKey($group, $companyId, 'name') => $label.' dengan nama itu sudah ada.'.
                    ($companyId === null ? '' : ' Di PT ini.'),
            ]);
        }
    }

    private function errorKey(string $group, ?int $companyId, string $field): string
    {
        return $group.'_'.($companyId ?? 'group').'_'.$field;
    }
}
