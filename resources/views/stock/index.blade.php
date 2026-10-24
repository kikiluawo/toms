<x-app-layout title="Stok">
    @php
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $qtyText = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), '.');
        $inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-navy-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-white/10 dark:bg-navy-900 dark:text-white dark:placeholder:text-white/30';
        $labelClass = 'text-sm font-semibold text-navy-700 dark:text-white';

        $payloadBranches = $branchesByCompany
            ->map(fn ($branches) => $branches->map(fn ($branch) => [
                'id' => (string) $branch->id,
                'name' => $branch->is_head_office ? $branch->name.' (Kantor Pusat)' : $branch->name,
            ])->values()->all())
            ->all();
    @endphp

    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Gudang</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Stok minyak per lokasi</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                    Saldo dihitung dari buku mutasi, jadi tidak bisa diketik langsung. Koreksi ditulis sebagai mutasi baru.
                </p>
            </div>
            <form method="GET" action="{{ route('stock.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="company_id" onchange="this.form.submit()"
                        class="h-9 rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    <option value="">Semua PT</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->code }}</option>
                    @endforeach
                </select>
                <select name="product_id" onchange="this.form.submit()"
                        class="h-9 rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    <option value="">Semua produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </form>
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
            <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
                <p class="text-sm font-bold text-navy-700 dark:text-white">Saldo saat ini ({{ $balances->count() }})</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px]">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Produk</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">PT</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Lokasi</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($balances as $row)
                            <tr class="transition hover:bg-lightPrimary/60 dark:hover:bg-white/5">
                                <td class="px-5 py-3">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">{{ $row->product?->name ?? 'Produk dihapus' }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $row->product?->category_label }}</p>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-white/50">{{ $row->company?->code ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-white/50">
                                    {{ $row->branch?->name ?? 'Level PT (belum ada lokasi)' }}
                                </td>
                                <td class="px-5 py-3 text-end">
                                    <p class="text-sm font-bold {{ (float) $row->balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-navy-700 dark:text-white' }}">
                                        {{ $qtyText($row->balance) }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $row->product?->unit_label }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada stok.</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Stok muncul setelah ada penerimaan order, atau setelah koreksi awal dibuat.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,360px)]">
            {{-- Movement history --}}
            <div class="horizon-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <p class="text-sm font-bold text-navy-700 dark:text-white">Mutasi terakhir</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">30 baris paling belakang.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px]">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Waktu</th>
                                <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Produk / lokasi</th>
                                <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Qty</th>
                                <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Sumber</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($movements as $movement)
                                <tr class="transition hover:bg-lightPrimary/60 dark:hover:bg-white/5">
                                    <td class="px-5 py-3 text-xs text-gray-500 dark:text-white/50">{{ $movement->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-semibold text-navy-700 dark:text-white">{{ $movement->product?->name ?? 'Produk dihapus' }}</p>
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $movement->branch?->name ?? 'level PT' }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-end">
                                        <p class="text-sm font-bold {{ $movement->direction === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $movement->direction === 'in' ? '+' : '−' }}{{ $qtyText($movement->qty) }}
                                        </p>
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $movement->product?->unit_label }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="text-xs text-gray-500 dark:text-white/50">
                                            {{ $movement->ref_type === 'purchase_receipt' ? 'Penerimaan' : 'Koreksi' }}
                                        </p>
                                        <p class="truncate text-[10px] text-gray-400">{{ $movement->note ?: '—' }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-xs text-gray-400">Belum ada mutasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Adjustment --}}
            <div class="space-y-4">
                @if ($companies->isEmpty())
                    <div class="horizon-card p-5 text-center">
                        <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada PT yang bisa kamu buka.</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Koreksi stok butuh akses minimal satu PT.</p>
                    </div>
                @else
                <form method="POST" action="{{ route('stock.adjustments.store') }}" class="horizon-card space-y-4 p-5"
                      x-data='{
                          companyId: {{ json_encode((string) $companies->first()->id, $jsonFlags) }},
                          branches: {{ json_encode($payloadBranches, $jsonFlags) }},
                          branchId: "",
                          branchOptions() {
                              return this.branches[this.companyId] || [];
                          },
                      }'>
                    @csrf

                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Koreksi stok</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-white/50">
                            Pakai ini untuk stok awal, selisih opname, atau minyak keluar tanpa dokumen. Isi alasannya.
                        </p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">PT</label>
                        <select name="company_id" x-model="companyId" @change="branchId = ''" class="{{ $inputClass }}">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Lokasi</label>
                        <select name="branch_id" x-model="branchId" class="{{ $inputClass }}">
                            <option value="">Level PT (belum ada lokasi)</option>
                            <template x-for="branch in branchOptions()" :key="branch.id">
                                <option :value="branch.id" x-text="branch.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Produk</label>
                        <select name="product_id" class="{{ $inputClass }}" required>
                            <option value="">Pilih produk</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->unit_label }}){{ $product->is_active ? '' : ' — nonaktif' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <div class="w-24">
                            <label class="{{ $labelClass }}">Arah</label>
                            <select name="direction" class="{{ $inputClass }}">
                                <option value="in">Masuk</option>
                                <option value="out">Keluar</option>
                            </select>
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="{{ $labelClass }}">Qty</label>
                            <input type="number" step="0.001" min="0.001" name="qty" required placeholder="0" class="{{ $inputClass }}">
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Alasan</label>
                        <input type="text" name="note" maxlength="255" required placeholder="Mis. opname 30 Okt, selisih 12 liter"
                               class="{{ $inputClass }}">
                    </div>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        Simpan koreksi
                    </button>

                    @if ($errors->any())
                        <ul class="list-disc space-y-0.5 pl-5 text-xs text-red-600 dark:text-red-400">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
