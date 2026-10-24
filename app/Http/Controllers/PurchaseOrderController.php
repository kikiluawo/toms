<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Order minyak oleh COO. PT dipilih di dalam form (bukan dari session), karena satu
 * COO bisa mengurus beberapa PT sekaligus.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $context = UserContext::for($request->user());

        $orders = PurchaseOrder::query()
            ->withCount('receipts')
            ->with(['company', 'branch', 'supplier', 'lines.product'])
            ->whereIn('company_id', $context->visibleCompanyIds())
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'userContext' => $context,
            'statuses' => config('oil.po_status'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $context = UserContext::for($request->user());

        if ($context->purchasableCompanies()->isEmpty()) {
            return redirect()->route('orders.index')
                ->with('error', 'Anda tidak berwenang memesan minyak di PT mana pun. Role COO diperlukan.');
        }

        return view('orders.create', $this->formData($request, null));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateOrder($request);
        $context = UserContext::for($request->user());

        abort_unless($context->canOrderFor($data['company_id']), 403, 'Hanya COO yang bisa memesan minyak untuk PT ini.');

        $order = DB::transaction(function () use ($data, $request) {
            $order = PurchaseOrder::create($this->headerData($data, $request->user()->id));
            $this->syncLines($order, $data['lines']);

            return $order;
        });

        return redirect()->route('orders.show', $order)
            ->with('status', 'Order '.$order->po_number.' tersimpan sebagai draft.');
    }

    public function show(Request $request, PurchaseOrder $order): View
    {
        $this->ensureVisible($request, $order);

        $order->load(['company', 'branch', 'supplier', 'creator', 'lines.product', 'receipts.receiver', 'receipts.branch', 'receipts.lines']);

        $context = UserContext::for($request->user());

        return view('orders.show', [
            'order' => $order,
            'canEdit' => $order->isDraft() && $context->canOrderFor($order->company_id),
            'canReceive' => $order->isOpenForReceipt() && $context->canViewCompany($order->company_id),
        ]);
    }

    public function edit(Request $request, PurchaseOrder $order): View|RedirectResponse
    {
        if ($response = $this->ensureDraftEditable($request, $order)) {
            return $response;
        }

        $order->load('lines.product');

        return view('orders.edit', $this->formData($request, $order));
    }

    public function update(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if ($response = $this->ensureDraftEditable($request, $order)) {
            return $response;
        }

        $data = $this->validateOrder($request);

        if ((int) $data['company_id'] !== $order->company_id) {
            throw ValidationException::withMessages([
                'company_id' => 'PT tidak bisa diubah setelah order dibuat. Hapus draft ini dan buat yang baru.',
            ]);
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->update($this->headerData($data, $request->user()->id));
            $order->lines()->delete();
            $this->syncLines($order, $data['lines']);
        });

        return redirect()->route('orders.show', $order)->with('status', 'Draft diperbarui.');
    }

    public function submit(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if ($response = $this->ensureDraftEditable($request, $order)) {
            return $response;
        }

        if ($order->lines()->count() === 0) {
            return back()->with('error', 'Order tanpa baris barang tidak bisa diajukan.');
        }

        $order->update([
            'status' => PurchaseOrder::SUBMITTED,
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
        ]);

        return back()->with('status', $order->po_number.' sudah diajukan dan bisa direalisasi.');
    }

    public function destroy(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if ($response = $this->ensureDraftEditable($request, $order)) {
            return $response;
        }

        $order->delete();

        return redirect()->route('orders.index')->with('status', 'Draft order dihapus.');
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'supplier_ref' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.qty_ordered' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0', 'max:999999999999'],
        ], [
            'lines.*.product_id.exists' => 'Ada baris order memakai produk yang sudah dinonaktifkan. Ganti produknya atau aktifkan kembali lewat katalog.',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function headerData(array $data, int $userId): array
    {
        return [
            'po_number' => $this->nextNumber($data['company_id'], $data['order_date']),
            'company_id' => (int) $data['company_id'],
            'branch_id' => $this->resolveBranch($data),
            'supplier_id' => (int) $data['supplier_id'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'supplier_ref' => $data['supplier_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => PurchaseOrder::DRAFT,
            'created_by' => $userId,
        ];
    }

    /**
     * A branch that does not belong to the chosen PT is a copy-paste accident, not a
     * valid destination, so it is dropped rather than trusted.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveBranch(array $data): ?int
    {
        if (empty($data['branch_id'])) {
            return null;
        }

        $branchId = (int) $data['branch_id'];
        $exists = DB::table('branches')
            ->where('id', $branchId)
            ->where('company_id', (int) $data['company_id'])
            ->where('is_active', true)
            ->exists();

        return $exists ? $branchId : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(PurchaseOrder $order, array $lines): void
    {
        foreach (array_values($lines) as $index => $line) {
            $product = Product::query()->findOrFail((int) $line['product_id']);

            $qty = round((float) $line['qty_ordered'], 3);
            $price = round((float) $line['unit_price'], 2);

            PurchaseOrderLine::create([
                'purchase_order_id' => $order->id,
                'product_id' => $product->id,
                'unit' => $product->unit,
                'qty_ordered' => $qty,
                'unit_price' => $price,
                'line_total' => round($qty * $price, 2),
                'sort_order' => $index,
            ]);
        }
    }

    private function nextNumber(int $companyId, string $orderDate): string
    {
        $year = date('Y', strtotime($orderDate));
        $code = Company::query()->findOrFail($companyId)->code;
        $prefix = 'PO-'.$code.'-'.$year.'-';

        $last = PurchaseOrder::query()
            ->where('po_number', 'like', $prefix.'%')
            ->orderByDesc('po_number')
            ->value('po_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Everything the order form needs. The catalogue is group-wide, so every PT the COO
     * may order at sees the same product list.
     *
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?PurchaseOrder $order): array
    {
        $companies = UserContext::for($request->user())->purchasableCompanies();

        return [
            'order' => $order,
            'companies' => $companies,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => CatalogController::orderable(),
            'branchesByCompany' => Branch::query()
                ->where('is_active', true)
                ->whereIn('company_id', $companies->pluck('id'))
                ->orderByDesc('is_head_office')
                ->orderBy('name')
                ->get()
                ->groupBy('company_id'),
        ];
    }

    private function ensureVisible(Request $request, PurchaseOrder $order): void
    {
        abort_unless(
            UserContext::for($request->user())->canViewCompany($order->company_id),
            403,
            'Order ini bukan milik PT yang bisa Anda buka.'
        );
    }

    /** Only the COO who may order for this PT, and only while it is still a draft. */
    private function ensureDraftEditable(Request $request, PurchaseOrder $order): ?RedirectResponse
    {
        $this->ensureVisible($request, $order);

        $context = UserContext::for($request->user());

        abort_unless($context->canOrderFor($order->company_id), 403, 'Hanya COO yang bisa mengubah order minyak.');

        if ($order->isDraft()) {
            return null;
        }

        return back()->with('error', $order->po_number.' sudah '.PurchaseOrder::statusLabel($order->status).', jadi tidak bisa diubah lagi.');
    }
}
