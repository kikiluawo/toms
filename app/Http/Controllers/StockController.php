<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\UserContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Stok dibaca dari buku mutasi, tidak pernah diketik sebagai saldo. Satu-satunya cara
 * mengubahnya selain penerimaan adalah koreksi yang juga menulis baris baru.
 */
class StockController extends Controller
{
    public function index(Request $request): View
    {
        $context = UserContext::for($request->user());

        $balances = $this->balanceQuery($context)
            ->when($request->input('company_id'), fn ($q, $id) => $q->where('company_id', (int) $id))
            ->when($request->input('branch_id'), fn ($q, $id) => $q->where('branch_id', (int) $id))
            ->when($request->input('product_id'), fn ($q, $id) => $q->where('product_id', (int) $id))
            ->with(['product', 'branch', 'company'])
            ->get()
            ->filter(fn (StockMovement $row) => abs((float) $row->balance) > 0.0005)
            ->sortBy([['company.name', 'asc'], ['branch.name', 'asc'], ['product.name', 'asc']]);

        // A retired product can still have litres sitting somewhere, and that leftover
        // is exactly what a koreksi is for, so it stays selectable here.
        $stockedIds = $this->movementQuery($context)->distinct()->pluck('product_id')->all();

        return view('stock.index', [
            'userContext' => $context,
            'balances' => $balances,
            'movements' => $this->movementQuery($context)->latest('id')->limit(30)->with(['product', 'branch', 'creator'])->get(),
            'companies' => $context->visibleCompanies(),
            'branchesByCompany' => Branch::query()
                ->where('is_active', true)
                ->whereIn('company_id', $context->visibleCompanyIds())
                ->orderByDesc('is_head_office')
                ->orderBy('name')
                ->get()
                ->groupBy('company_id'),
            'products' => Product::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $stockedIds))
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Koreksi saldo (selisih opname, minyak tumpah, pemakaian tanpa dokumen).
     * Arah ditentukan dari selisihnya supaya saldo tetap hasil penjumlahan buku mutasi.
     */
    public function storeAdjustment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'direction' => ['required', Rule::in([StockMovement::IN, StockMovement::OUT])],
            'qty' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $context = UserContext::for($request->user());
        $companyId = (int) $data['company_id'];
        $branchId = empty($data['branch_id']) ? null : (int) $data['branch_id'];

        abort_unless($context->canActOn($companyId, $branchId), 403, 'Anda tidak bertugas di lokasi ini.');

        $product = Product::query()->findOrFail((int) $data['product_id']);

        $qty = round((float) $data['qty'], 3);

        if ($data['direction'] === StockMovement::OUT) {
            $available = $this->balanceOf($companyId, $branchId, $product->id);

            if ($qty > $available + 0.0005) {
                return back()->with('error', 'Stok '.$product->name.' di lokasi ini hanya '.
                    number_format($available, 3, ',', '.').' '.$product->unit_label.'. Keluar tidak boleh lebih besar dari saldo.');
            }
        }

        StockMovement::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'direction' => $data['direction'],
            'qty' => $qty,
            'ref_type' => 'adjustment',
            'note' => $data['note'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Koreksi tercatat: '.($data['direction'] === StockMovement::IN ? 'masuk' : 'keluar').
            ' '.number_format($qty, 3, ',', '.').' '.$product->unit_label.'.');
    }

    /** The same visibility rule feeds both the balance table and the history table. */
    private function balanceQuery(UserContext $context): Builder
    {
        return $this->constrain(StockMovement::balanceQuery(), $context)->orderBy('company_id');
    }

    private function movementQuery(UserContext $context): Builder
    {
        return $this->constrain(StockMovement::query(), $context);
    }

    /**
     * A location-scope member only sees the stock sitting at the locations they are
     * assigned to; everyone else sees the whole PT.
     */
    private function constrain(Builder $query, UserContext $context): Builder
    {
        return $query->where(function ($outer) use ($context) {
            foreach ($context->visibleCompanies() as $company) {
                $branchIds = $context->visibleBranchIds($company->id);

                $outer->orWhere(function ($inner) use ($company, $branchIds) {
                    $inner->where('company_id', $company->id);

                    if ($branchIds !== []) {
                        $inner->whereIn('branch_id', $branchIds);
                    }
                });
            }
        });
    }

    private function balanceOf(int $companyId, ?int $branchId, int $productId): float
    {
        return (float) StockMovement::balanceQuery()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->when($branchId === null, fn ($q) => $q->whereNull('branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->value('balance');
    }
}
