<x-app-layout title="Order Minyak">
    @php
        $statusTone = [
            'draft' => 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-white/40',
            'submitted' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            'completed' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        ];
        $canOrder = $userContext->purchasableCompanies()->isNotEmpty();
    @endphp

    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Pengadaan</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Order minyak</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                    {{ $orders->total() }} order. Simpan sebagai draft dulu, lalu ajukan; setelah diajukan baru bisa direalisasi.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('orders.index') }}" class="flex items-center gap-2">
                    <select name="status" onchange="this.form.submit()"
                            class="h-9 rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @if ($canOrder)
                    <a href="{{ route('orders.create') }}" class="flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                        <x-icon name="plus" class="h-4 w-4" />
                        Buat order
                    </a>
                @endif
            </div>
        </div>

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

        <div class="horizon-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">No. order</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">PT / lokasi</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Pemasok</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Qty</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Nilai</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Realisasi</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($orders as $order)
                            @php
                                $ordered = $order->lines->sum('qty_ordered');
                                $received = $order->lines->sum('qty_received');
                            @endphp
                            <tr class="align-top transition hover:bg-lightPrimary/60 dark:hover:bg-white/5">
                                <td class="px-5 py-4">
                                    <a href="{{ route('orders.show', $order) }}" class="text-sm font-bold text-navy-700 hover:text-brand-600 dark:text-white dark:hover:text-brand-300">
                                        {{ $order->po_number }}
                                    </a>
                                    <p class="mt-0.5 text-[10px] uppercase tracking-wide text-gray-400">
                                        {{ $order->order_date->format('d M Y') }}
                                        @if ($order->expected_date) • estimasi {{ $order->expected_date->format('d M Y') }} @endif
                                    </p>
                                    <span class="mt-1 inline-flex rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide {{ $statusTone[$order->status] ?? 'bg-gray-100 text-gray-500' }}">
                                        {{ \App\Models\PurchaseOrder::statusLabel($order->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">{{ $order->company->name }}</p>
                                    <p class="mt-0.5 text-[10px] uppercase tracking-wide text-gray-400">
                                        {{ $order->branch?->name ?? 'Seluruh lokasi' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">{{ $order->supplier->name }}</p>
                                    @if ($order->supplier_ref)
                                        <p class="mt-0.5 truncate text-[10px] text-gray-400">ref {{ $order->supplier_ref }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-end">
                                    <p class="text-sm font-bold text-navy-700 dark:text-white">{{ rtrim(rtrim(number_format($ordered, 3, ',', '.'), '0'), '.') }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $order->lines->pluck('unit')->unique()->join(', ') ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-end">
                                    <p class="text-sm font-bold text-navy-700 dark:text-white">Rp {{ number_format($order->lines->sum('line_total'), 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    @php $percent = $ordered > 0 ? min(100, round($received / $ordered * 100)) : 0; @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                            <div class="h-full rounded-full bg-oil-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-400">{{ $percent }}%</span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-gray-400">{{ $order->receipts_count ?? 0 }} penerimaan</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('orders.show', $order) }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-lightPrimary hover:text-navy-700 dark:text-white/60 dark:hover:bg-white/10 dark:hover:text-white" title="Buka order">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada order minyak.</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-white/50">
                                        {{ $canOrder ? 'Buat draft pertama, lalu ajukan supaya bisa direalisasi.' : 'Role COO diperlukan untuk memesan minyak.' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-gray-100 p-4 dark:border-white/5">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
