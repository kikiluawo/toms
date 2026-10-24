<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Master data PT. Creating a company changes the shape of the whole system, so this
 * screen is reserved for the system account and is never reachable by a PT-level role.
 */
class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSystem($request);

        $companies = Company::query()
            ->with(['branches' => fn ($query) => $query->orderBy('name')])
            ->withCount(['users', 'branches', 'departments', 'positions'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('companies.index', [
            'companies' => $companies,
            'userContext' => UserContext::for($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSystem($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('companies', 'code')],
        ]);

        $company = Company::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
        ]);

        return back()->with('status', 'PT '.$company->name.' berhasil dibuat.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSystem($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('companies', 'code')->ignore($company->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $company->update([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'PT diperbarui.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSystem($request);

        // Deleting a company cascades its branches, dictionary and memberships away.
        if ($company->users()->exists()) {
            return back()->with('error', $company->name.' masih punya anggota. Nonaktifkan saja, jangan hapus.');
        }

        // Orders and stock are history and refuse to be erased with the PT.
        if ($company->purchaseOrders()->exists() || $company->stockMovements()->exists()) {
            return back()->with('error', $company->name.' sudah punya riwayat order/stok. Nonaktifkan saja, jangan hapus.');
        }

        DB::transaction(function () use ($company) {
            $company->branches()->each(fn (Branch $branch) => $branch->users()->detach());
            $company->branches()->delete();
            Department::query()->where('company_id', $company->id)->delete();
            Position::query()->where('company_id', $company->id)->delete();
            $company->delete();
        });

        return back()->with('status', 'PT '.$company->name.' berhasil dihapus.');
    }

    public function storeBranch(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSystem($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash'],
            'is_head_office' => ['nullable', 'boolean'],
        ]);

        $duplicate = $company->branches()->whereRaw('LOWER(code) = ?', [mb_strtolower($data['code'])])->exists();

        if ($duplicate) {
            return back()->with('error', 'Kode lokasi '.$data['code'].' sudah dipakai di '.$company->name.'.');
        }

        $wasEmpty = $company->branches()->count() === 0;

        $branch = $company->branches()->create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'is_head_office' => $request->boolean('is_head_office'),
        ]);

        if ($wasEmpty) {
            // Staff hired while this PT had no location worked at the PT itself. Once the
            // first real location exists they must inherit it, or their access goes blank.
            $company->users()
                ->where('company_user.data_scope', 'branch')
                ->whereDoesntHave('branches', fn ($query) => $query->where('branches.company_id', $company->id))
                ->get()
                ->each(fn (User $user) => $user->branches()->attach($branch->id));
        }

        return back()->with('status', 'Lokasi berhasil ditambahkan.');
    }

    public function destroyBranch(Request $request, Branch $branch): RedirectResponse
    {
        $this->ensureSystem($request);

        if ($branch->users()->exists()) {
            return back()->with('error', 'Lokasi '.$branch->name.' masih dipakai user.');
        }

        if ($branch->purchaseOrders()->exists() || $branch->purchaseReceipts()->exists() || $branch->stockMovements()->exists()) {
            return back()->with('error', 'Lokasi '.$branch->name.' sudah dipakai dalam order/penerimaan/stok. Nonaktifkan saja.');
        }

        $branch->delete();

        return back()->with('status', 'Cabang berhasil dihapus.');
    }

    private function ensureSystem(Request $request): void
    {
        abort_unless(UserContext::for($request->user())->isSystem(), 403, 'Hanya akun sistem yang bisa mengubah daftar PT.');
    }
}
