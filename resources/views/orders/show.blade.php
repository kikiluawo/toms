<x-app-layout title="Detail Order">
    @php
        $qtyText = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), '.');
        $statusTone = [
            'draft' => 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-white/40',
            'submitted' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            'completed' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        ];
    @endphp

    <div class="mt-3 space-y-5">
        @if (session('status'))
            <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        <div class="horizon-card flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-2 text-xl font-bold text-navy-700 dark:text-white">
                    {{ $order->po_number }}
                    <span class="rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide {{ $statusTone[$order->status] ?? 'bg-gray-100 text-gray-500' }}">
                        {{ \App\Models\PurchaseOrder::statusLabel($order->status) }}
                    </span>
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                    {{ $order->company->name }} •
                    {{ $order->branch?->name ?? 'seluruh lokasi' }} •
                    {{ $order->supplier->name }} •
                    order {{ $order->order_date->format('d M Y') }}
                    @if ($order->expected_date) • rencana tiba {{ $order->expected_date->format('d M Y') }} @endif
                </p>
                <p class="mt-0.5 text-xs text-gray-400">
                    Dibuat oleh {{ $order->creator?->name ?? '—' }}
                    @if ($order->submitted_at) • diajukan {{ $order->submitted_at->format('d M Y H:i') }} oleh {{ $order->submitter?->name ?? '—' }} @endif
                    @if ($order->completed_at) • selesai {{ $order->completed_at->format('d M Y H:i') }} @endif
                </p>
                @if ($order->supplier_ref)
                    <p class="mt-0.5 text-xs text-gray-400">Rujukan pemasok: {{ $order->supplier_ref }}</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($canEdit)
                    <a href="{{ route('orders.edit', $order) }}" class="flex items-center gap-2 rounded-xl border border-gray-200 px-3.5 py-2 text-sm font-bold text-gray-600 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/60 dark:hover:bg-white/5">
                        <x-icon name="pencil" class="h-4 w-4" />
                        Ubah draft
                    </a>
                    <form method="POST" action="{{ route('orders.submit', $order) }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 rounded-xl bg-brand-500 px-3.5 py-2 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                            <x-icon name="arrow-up" class="h-4 w-4" />
                            Ajukan order
                        </button>
                    </form>
                    <form method="POST" action="{{ route('orders.destroy', $order) }}"
                          onsubmit="return confirm('Hapus draft {{ $order->po_number }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus draft">
                            <x-icon name="trash" class="h-4 w-4" />
                        </button>
                    </form>
                @endif

                @if ($canReceive)
                    <a href="{{ route('receipts.create', $order) }}" class="flex items-center gap-2 rounded-xl bg-oil-500 px-3.5 py-2 text-sm font-bold text-white transition hover:bg-oil-600 dark:bg-oil-400 dark:hover:bg-oil-300">
                        <x-icon name="plus" class="h-4 w-4" />
                        Catat penerimaan
                    </a>
                @endif
            </div>
        </div>

        {{-- Lines --}}
        <div class="horizon-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/5">
                <p class="text-sm font-bold text-navy-700 dark:text-white">Barang dipesan ({{ $order->lines->count() }})</p>
                <p class="text-sm font-bold text-navy-700 dark:text-white">Rp {{ number_format($order->totalAmount(), 0, ',', '.') }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px]">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Produk</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Dipesan</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Diterima</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Sisa</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Harga</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($order->lines as $line)
                            <tr class="align-top transition hover:bg-lightPrimary/60 dark:hover:bg-white/5">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">{{ $line->product?->name ?? 'Produk dihapus' }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $line->product?->code }} • satuan {{ $line->unit }}</p>
                                </td>
                                <td class="px-5 py-4 text-end text-sm font-bold text-navy-700 dark:text-white">{{ $qtyText($line->qty_ordered) }}</td>
                                <td class="px-5 py-4 text-end text-sm font-semibold text-oil-600 dark:text-oil-300">{{ $qtyText($line->qty_received) }}</td>
                                <td class="px-5 py-4 text-end text-sm {{ $line->outstandingQty() > 0 ? 'font-bold text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                                    {{ $qtyText($line->outstandingQty()) }}
                                </td>
                                <td class="px-5 py-4 text-end text-sm text-gray-500 dark:text-white/50">Rp {{ number_format($line->unit_price, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-end text-sm font-bold text-navy-700 dark:text-white">Rp {{ number_format($line->line_total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-xs text-gray-400">Draft ini belum punya baris barang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            {{-- Receipts --}}
            <div class="horizon-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <p class="text-sm font-bold text-navy-700 dark:text-white">Realisasi / penerimaan ({{ $order->receipts->count() }})</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">Setiap penerimaan otomatis menambah stok di lokasinya.</p>
                </div>

                <div class="space-y-3 p-5">
                    @forelse ($order->receipts->sortByDesc('received_at') as $receipt)
                        <div class="rounded-xl border border-gray-100 p-3 dark:border-white/5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-bold text-navy-700 dark:text-white">{{ $receipt->receipt_number }}</p>
                                <p class="text-sm font-bold text-oil-600 dark:text-oil-300">{{ $qtyText($receipt->totalQty()) }} total masuk</p>
                            </div>
                            <p class="mt-0.5 text-[10px] uppercase tracking-wide text-gray-400">
                                {{ $receipt->received_at->format('d M Y H:i') }} •
                                {{ $receipt->branch?->name ?? 'level PT' }} •
                                dokumen {{ $receipt->doc_number }} •
                                {{ $receipt->receiver?->name ?? '—' }}
                                @if ($receipt->fleet_number) • armasi {{ $receipt->fleet_number }} @endif
                                @if ($receipt->driver_name) • {{ $receipt->driver_name }} @endif
                            </p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($receipt->lines as $receiptLine)
                                    @php $orderLine = $order->lines->firstWhere('id', $receiptLine->purchase_order_line_id); @endphp
                                    <li class="flex items-center justify-between text-xs text-gray-500 dark:text-white/50">
                                        <span class="truncate">{{ $orderLine?->product?->name ?? 'Baris dihapus' }}</span>
                                        <span class="font-semibold text-navy-700 dark:text-white">{{ $qtyText($receiptLine->qty) }} {{ $orderLine?->unit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-200 px-3 py-6 text-center text-xs text-gray-400 dark:border-white/10">
                            {{ $order->isDraft() ? 'Draft belum bisa direalisasi — ajukan dulu.' : 'Belum ada barang masuk untuk order ini.' }}
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Notes --}}
            <div class="horizon-card p-5">
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Catatan order</p>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-600 dark:text-white/60">{{ $order->notes ?: '—' }}</p>

                <div class="mt-5 space-y-2 border-t border-gray-100 pt-4 dark:border-white/5">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Progres</p>
                    @php
                        $ordered = $order->lines->sum('qty_ordered');
                        $received = $order->lines->sum('qty_received');
                        $percent = $ordered > 0 ? min(100, round($received / $ordered * 100)) : 0;
                    @endphp
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full bg-oil-500" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-white/50">{{ $percent }}% dari quantity order sudah masuk.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
