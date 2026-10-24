<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\StockMovement;
use App\Support\UserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Realisasi/penerimaan barang. Satu order boleh diterima beberapa kali (truk datang
 * bertahap), dan tiap penerimaan otomatis menulis mutasi stok masuk.
 */
class PurchaseReceiptController extends Controller
{
    public function create(Request $request, PurchaseOrder $order): View
    {
        $this->ensureReceivable($request, $order);

        $order->load(['lines.product', 'branch', 'company', 'receipts']);

        return view('receipts.create', [
            'order' => $order,
            'branches' => $order->company->branches->where('is_active', true),
        ]);
    }

    public function store(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->ensureReceivable($request, $order);

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $order->company_id)],
            'doc_number' => ['required', 'string', 'max:60'],
            'received_at' => ['required', 'date'],
            'fleet_number' => ['nullable', 'string', 'max:20'],
            'driver_name' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.qty' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $lines = collect($order->lines)->keyBy('id');
        $received = collect($data['lines'])
            ->filter(fn ($line) => ! empty($line['qty']))
            ->mapWithKeys(fn ($line, $lineId) => [(int) $lineId => round((float) $line['qty'], 3)]);

        if ($received->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Isi minimal satu qty yang diterima.']);
        }

        foreach ($received as $lineId => $qty) {
            $line = $lines->get($lineId);

            if (! $line) {
                throw ValidationException::withMessages(['lines' => 'Baris order tidak dikenali.']);
            }

            if ($qty > $line->outstandingQty() + 0.0005) {
                throw ValidationException::withMessages([
                    'lines.'.$lineId.'.qty' => $line->product->name.': sisa order hanya '.
                        number_format($line->outstandingQty(), 3, ',', '.').' '.$line->unit.'.',
                ]);
            }
        }

        $branchId = $this->resolveBranch($order, $data['branch_id'] ?? null);

        if ($branchId === null && $order->company->branches()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Pilih lokasi tujuan penerimaan.']);
        }

        $receipt = DB::transaction(function () use ($order, $data, $received, $branchId, $request, $lines) {
            $receipt = PurchaseReceipt::create([
                'purchase_order_id' => $order->id,
                'receipt_number' => $this->nextReceiptNumber($order),
                'doc_number' => $data['doc_number'],
                'received_at' => $data['received_at'],
                'branch_id' => $branchId,
                'fleet_number' => $data['fleet_number'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $request->user()->id,
            ]);

            foreach ($received as $lineId => $qty) {
                $line = $lines[$lineId];

                PurchaseReceiptLine::create([
                    'purchase_receipt_id' => $receipt->id,
                    'purchase_order_line_id' => $lineId,
                    'qty' => $qty,
                ]);

                $line->increment('qty_received', $qty);

                StockMovement::create([
                    'company_id' => $order->company_id,
                    'branch_id' => $branchId,
                    'product_id' => $line->product_id,
                    'direction' => StockMovement::IN,
                    'qty' => $qty,
                    'ref_type' => 'purchase_receipt',
                    'ref_id' => $receipt->id,
                    'note' => $order->po_number.' / '.$receipt->doc_number,
                    'created_by' => $request->user()->id,
                ]);
            }

            $this->closeOrderWhenFull($order);

            return $receipt;
        });

        return redirect()->route('orders.show', $order)
            ->with('status', 'Penerimaan '.$receipt->receipt_number.' tercatat, stok bertambah.');
    }

    /**
     * A PT without locations receives at company level; once it has locations the stock
     * must point at one of them or the balance would be unplaceable.
     */
    private function resolveBranch(PurchaseOrder $order, int|string|null $branchId): ?int
    {
        if (is_numeric($branchId) && (int) $branchId > 0) {
            return (int) $branchId;
        }

        return $order->branch_id && $order->company->branches()->whereKey($order->branch_id)->exists()
            ? $order->branch_id
            : null;
    }

    private function nextReceiptNumber(PurchaseOrder $order): string
    {
        $sequence = $order->receipts()->count() + 1;

        return $order->po_number.'-R'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    private function closeOrderWhenFull(PurchaseOrder $order): void
    {
        $order->load('lines');

        if ($order->lines->isNotEmpty() && $order->lines->every(fn ($line) => $line->isFullyReceived())) {
            $order->update(['status' => PurchaseOrder::COMPLETED, 'completed_at' => now()]);
        }
    }

    /**
     * Receiving is done by the people at the destination, not only by the COO who
     * ordered: anyone who may act on that company/branch may record a delivery.
     */
    private function ensureReceivable(Request $request, PurchaseOrder $order): void
    {
        $context = UserContext::for($request->user());

        abort_unless($context->canViewCompany($order->company_id), 403);

        if (! $order->isOpenForReceipt()) {
            abort(403, $order->status === PurchaseOrder::DRAFT
                ? 'Order masih draft — ajukan dulu sebelum mencatat penerimaan.'
                : 'Order ini sudah selesai diterima semua.');
        }
    }
}
