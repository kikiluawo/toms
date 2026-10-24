<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pemasok juga data bersama lintas PT, makanya berdiri sendiri sebagai CRUD dan tidak
 * lagi numpang di layar katalog.
 */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $context = UserContext::for($request->user());

        return view('suppliers.index', [
            'userContext' => $context,
            'suppliers' => Supplier::query()->withCount('purchaseOrders')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureGroup($request);

        $supplier = Supplier::create($this->validated($request));

        return back()->with('status', 'Pemasok '.$supplier->name.' berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->ensureGroup($request);

        $supplier->update($this->validated($request, $supplier));

        return back()->with('status', 'Pemasok '.$supplier->name.' diperbarui.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->ensureGroup($request);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', $supplier->name.' sudah pernah diorder, jadi tidak bisa dihapus. Nonaktifkan saja.');
        }

        $supplier->delete();

        return back()->with('status', 'Pemasok berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', Rule::unique('suppliers', 'code')->ignore($supplier?->id)],
            'contact' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = $data['code'] ? strtoupper($data['code']) : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function ensureGroup(Request $request): void
    {
        abort_unless(
            UserContext::for($request->user())->isGroup(),
            403,
            'Pemasok dipakai bersama lintas PT, jadi hanya user level grup yang bisa mengubahnya.'
        );
    }
}
