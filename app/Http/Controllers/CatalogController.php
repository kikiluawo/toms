<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Katalog minyak bersifat satu untuk seluruh grup: satu drum TDO adalah barang yang sama
 * di PT mana pun, jadi tidak ada lagi pemisahan per PT di sini.
 */
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $context = UserContext::for($request->user());

        return view('catalog.index', [
            'userContext' => $context,
            'products' => Product::query()->orderBy('category')->orderBy('name')->get()->groupBy('category'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureGroup($request);

        $product = Product::create($this->validated($request) + ['is_active' => true]);

        return back()->with('status', 'Produk '.$product->name.' berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureGroup($request);

        $data = $this->validated($request, $product);

        // The unit a product is measured in is also the unit of every stock row that
        // already exists, so changing it afterwards would silently rewrite history.
        $hasHistory = $product->orderLines()->exists() || $product->stockMovements()->exists();

        if ($hasHistory && $data['unit'] !== $product->unit) {
            return back()->with('error', 'Satuan '.$product->name.' tidak bisa diubah — produk ini sudah punya order/stok. '
                .'Buat produk baru dengan satuan yang benar, lalu nonaktifkan yang lama.');
        }

        $product->update($data);

        return back()->with('status', 'Produk '.$product->name.' diperbarui.');
    }

    /** Retire a product that is no longer sold, or bring it back, without touching history. */
    public function toggleActive(Request $request, Product $product): RedirectResponse
    {
        $this->ensureGroup($request);

        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->name.' '.($product->is_active ? 'aktif lagi.' : 'dinonaktifkan — tidak muncul lagi di form order.'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureGroup($request);

        // An ordered or stocked item must stay resolvable, so it is retired instead of erased.
        if ($product->orderLines()->exists() || $product->stockMovements()->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('error', $product->name.' sudah pernah diorder atau punya stok, jadi hanya dinonaktifkan.');
        }

        $product->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    /**
     * Products a document form may offer.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public static function orderable()
    {
        return Product::active()->orderBy('category')->orderBy('name')->get();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('products', 'code')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(array_keys(config('oil.categories')))],
            'unit' => ['required', Rule::in(array_keys(config('oil.units')))],
        ]);

        $data['code'] = strtoupper($data['code']);

        return $data;
    }

    private function ensureGroup(Request $request): void
    {
        abort_unless(
            UserContext::for($request->user())->isGroup(),
            403,
            'Katalog dipakai bersama lintas PT, jadi hanya user level grup yang bisa mengubahnya.'
        );
    }
}
