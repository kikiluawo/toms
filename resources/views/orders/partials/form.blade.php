@php
    $inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-navy-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-white/10 dark:bg-navy-900 dark:text-white dark:placeholder:text-white/30';
    $labelClass = 'text-sm font-semibold text-navy-700 dark:text-white';
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    $initialCompanyId = (string) old('company_id', $order?->company_id ?? $companies->first()->id);
    $initialBranchId = (string) old('branch_id', $order?->branch_id ?? '');

    $payloadRows = collect(old('lines', $order?->lines?->map(fn ($line) => [
        'product_id' => (string) $line->product_id,
        'qty_ordered' => (string) $line->qty_ordered,
        'unit_price' => (string) $line->unit_price,
    ])->values()->all() ?: []))
        ->map(fn ($line, $index) => [
            'key' => $index,
            'product_id' => (string) ($line['product_id'] ?? ''),
            'qty' => (string) ($line['qty_ordered'] ?? ''),
            'unit_price' => (string) ($line['unit_price'] ?? ''),
        ])
        ->whenEmpty(fn ($rows) => collect([['key' => 0, 'product_id' => '', 'qty' => '', 'unit_price' => '']]))
        ->values()
        ->all();

    $payloadProducts = $products->map(fn ($product) => [
        'id' => (string) $product->id,
        'name' => $product->name,
        'code' => $product->code,
        'unit' => $product->unit_label,
    ])->all();

    $payloadBranches = $branchesByCompany
        ->map(fn ($branches) => $branches->map(fn ($branch) => [
            'id' => (string) $branch->id,
            'name' => $branch->is_head_office ? $branch->name.' (Kantor Pusat)' : $branch->name,
        ])->values()->all())
        ->all();
@endphp

@if ($errors->any())
    <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-500 dark:bg-red-500/20 dark:text-red-400">
            <x-icon name="info" class="h-4 w-4" />
        </span>
        <div>
            <p class="text-sm font-bold text-red-600 dark:text-red-400">Periksa kembali input berikut:</p>
            <ul class="mt-1 list-disc pl-5 text-sm text-red-600 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6"
    x-data='{
        companyId: {{ json_encode($initialCompanyId, $jsonFlags) }},
        branchId: {{ json_encode($initialBranchId, $jsonFlags) }},
        products: {{ json_encode($payloadProducts, $jsonFlags) }},
        branches: {{ json_encode($payloadBranches, $jsonFlags) }},
        rows: {{ json_encode($payloadRows, $jsonFlags) }},
        seq: {{ count($payloadRows) }},
        branchOptions() {
            return this.branches[this.companyId] || [];
        },
        product(id) {
            return this.products.find((item) => item.id === id);
        },
        lineTotal(row) {
            return (Number(row.qty) || 0) * (Number(row.unit_price) || 0);
        },
        totalAmount() {
            return this.rows.reduce((total, row) => total + this.lineTotal(row), 0);
        },
        money(value) {
            return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(value || 0);
        },
        addRow() {
            this.rows.push({ key: this.seq++, product_id: "", qty: "", unit_price: "" });
        },
        removeRow(index) {
            if (this.rows.length > 1) this.rows.splice(index, 1);
        },
        onCompanyChange() {
            this.branchId = "";
        },
    }'
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Header --}}
            <section class="horizon-card p-5">
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Informasi order</p>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">PT pemesan</label>
                        <select name="company_id" x-model="companyId" @change="onCompanyChange()" class="{{ $inputClass }}">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @if ($order !== null)
                            <p class="mt-1 text-[10px] text-gray-400">PT tidak bisa diubah setelah order dibuat.</p>
                        @endif
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Lokasi tujuan</label>
                        <select name="branch_id" x-model="branchId" class="{{ $inputClass }}">
                            <option value="">Seluruh lokasi PT</option>
                            <template x-for="branch in branchOptions()" :key="branch.id">
                                <option :value="branch.id" x-text="branch.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-[10px] text-gray-400">Kosongkan bila order belum tahu akan dibongkar di lokasi mana.</p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Pemasok</label>
                        <select name="supplier_id" class="{{ $inputClass }}" required>
                            <option value="">Pilih pemasok</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order?->supplier_id) === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">No. rujukan pemasok</label>
                        <input type="text" name="supplier_ref" maxlength="60" placeholder="No. QU / penawaran"
                               value="{{ old('supplier_ref', $order?->supplier_ref) }}" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Tanggal order</label>
                        <input type="date" name="order_date" required
                               value="{{ old('order_date', optional($order?->order_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                               class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Rencana tiba</label>
                        <input type="date" name="expected_date"
                               value="{{ old('expected_date', optional($order?->expected_date)->format('Y-m-d')) }}"
                               class="{{ $inputClass }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Catatan</label>
                        <textarea name="notes" rows="2" maxlength="2000" placeholder="Mis. pembayaran tempo 30 hari, permintaan operasional tambang."
                                  class="{{ $inputClass }}">{{ old('notes', $order?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            {{-- Lines --}}
            <section class="horizon-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Barang dipesan</p>
                    <button type="button" @click="addRow()" class="flex h-9 items-center gap-1.5 rounded-xl border border-brand-200 px-3 text-xs font-bold text-brand-600 transition hover:bg-brand-500/10 dark:border-brand-400/30 dark:text-brand-300">
                        <x-icon name="plus" class="h-3.5 w-3.5" />
                        Tambah baris
                    </button>
                </div>

                <div class="mt-3 space-y-2">
                    <div class="hidden gap-2 px-1 text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400 sm:grid sm:grid-cols-[minmax(0,1fr)_110px_140px_140px_32px]">
                        <span>Produk</span>
                        <span>Qty</span>
                        <span>Harga / satuan</span>
                        <span class="text-end">Subtotal</span>
                        <span></span>
                    </div>

                    <template x-for="(row, index) in rows" :key="row.key">
                        <div class="grid gap-2 rounded-xl border border-gray-100 p-2 dark:border-white/5 sm:grid-cols-[minmax(0,1fr)_110px_140px_140px_32px] sm:items-center">
                            <select :name="`lines[${index}][product_id]`" x-model="row.product_id" class="h-9 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                <option value="">Pilih produk</option>
                                <template x-for="item in products" :key="item.id">
                                    <option :value="item.id" x-text="`${item.name} (${item.unit})`"></option>
                                </template>
                            </select>

                            <div class="flex items-center gap-1">
                                <input type="number" step="0.001" min="0" :name="`lines[${index}][qty_ordered]`" x-model="row.qty" placeholder="0"
                                       class="h-9 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                <span class="shrink-0 text-[10px] uppercase text-gray-400" x-text="product(row.product_id)?.unit || ''"></span>
                            </div>

                            <input type="number" step="1" min="0" :name="`lines[${index}][unit_price]`" x-model="row.unit_price" placeholder="Rp"
                                   class="h-9 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">

                            <p class="text-sm font-bold text-navy-700 dark:text-white sm:text-end" x-text="money(lineTotal(row))"></p>

                            <button type="button" @click="removeRow(index)" class="flex h-8 w-8 items-center justify-center self-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus baris">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        {{-- Summary --}}
        <div class="space-y-4 xl:sticky xl:top-6 xl:self-start">
            <div class="horizon-card p-5">
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Ringkasan</p>
                <p class="mt-3 text-[10px] uppercase tracking-wide text-gray-400">Nilai order</p>
                <p class="text-2xl font-bold text-navy-700 dark:text-white" x-text="money(totalAmount())"></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-white/50" x-text="`${rows.length} baris barang`"></p>

                <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 dark:border-white/5">
                    <p class="text-xs text-gray-500 dark:text-white/50">
                        Order tersimpan sebagai <span class="font-bold text-navy-700 dark:text-white">draft</span>.
                        Setelah diajukan, order tidak bisa diedit lagi dan bisa mulai direalisasi.
                    </p>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <button type="submit" class="flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        {{ $order ? 'Perbarui draft' : 'Simpan sebagai draft' }}
                    </button>
                    <a href="{{ $order ? route('orders.show', $order) : route('orders.index') }}" class="flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-500 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/50 dark:hover:bg-white/5">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
