<x-app-layout title="Catat Penerimaan">
    @php
        $inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-navy-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-white/10 dark:bg-navy-900 dark:text-white dark:placeholder:text-white/30';
        $labelClass = 'text-sm font-semibold text-navy-700 dark:text-white';
        $qtyText = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), '.');
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

        $rowsPayload = $order->lines->map(fn ($line) => [
            'qty' => (string) old('lines.'.$line->id.'.qty', ''),
        ])->values()->all();
    @endphp

    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Realisasi</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Penerimaan {{ $order->po_number }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                    {{ $order->company->name }} • {{ $order->supplier->name }} •
                    sisa yang belum masuk {{ $qtyText($order->lines->sum(fn ($line) => $line->outstandingQty())) }} unit.
                </p>
            </div>
            <a href="{{ route('orders.show', $order) }}" class="flex items-center gap-2 self-start rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-500 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/50 dark:hover:bg-white/5">
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali
            </a>
        </div>

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

        <form method="POST" action="{{ route('receipts.store', $order) }}" class="grid grid-cols-1 gap-6 xl:grid-cols-3"
              x-data='{
                  rows: {{ json_encode($rowsPayload, $jsonFlags) }},
                  total() {
                      return this.rows.reduce((sum, row) => sum + (Number(row.qty) || 0), 0);
                  },
              }'>
            @csrf

            <div class="space-y-6 xl:col-span-2">
                <section class="horizon-card p-5">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Dokumen penerimaan</p>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">No. surat jalan / DO</label>
                            <input type="text" name="doc_number" maxlength="60" required placeholder="Mis. SJ-0451"
                                   value="{{ old('doc_number') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Tanggal diterima</label>
                            <input type="date" name="received_at" required
                                   value="{{ old('received_at', now()->format('Y-m-d')) }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Lokasi pembongkaran</label>
                            <select name="branch_id" class="{{ $inputClass }}">
                                @if ($branches->isEmpty())
                                    <option value="">Level PT (belum ada lokasi)</option>
                                @else
                                    <option value="">Pilih lokasi</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $order->branch_id) === (string) $branch->id)>
                                            {{ $branch->name }}{{ $branch->is_head_office ? ' (Kantor Pusat)' : '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <p class="mt-1 text-[10px] text-gray-400">Stok bertambah di lokasi ini.</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Armada / tangki</label>
                            <input type="text" name="fleet_number" maxlength="20" placeholder="Mis. B-9042-TCF"
                                   value="{{ old('fleet_number') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Nama sopir</label>
                            <input type="text" name="driver_name" maxlength="80" value="{{ old('driver_name') }}" class="{{ $inputClass }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $labelClass }}">Catatan</label>
                            <textarea name="notes" rows="2" maxlength="2000" placeholder="Mis. segel utuh, suhu 28 C, selisih ditoleransi."
                                      class="{{ $inputClass }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="horizon-card p-5">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Qty yang diterima</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-white/50">
                        Kosongkan baris yang belum datang. Satu order boleh diterima beberapa kali.
                    </p>

                    <div class="mt-3 space-y-2">
                        <div class="hidden gap-2 px-1 text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400 sm:grid sm:grid-cols-[minmax(0,1fr)_120px_120px]">
                            <span>Produk</span>
                            <span>Sisa order</span>
                            <span>Masuk sekarang</span>
                        </div>

                        @foreach ($order->lines as $line)
                            @php $outstanding = $line->outstandingQty(); @endphp
                            <div class="grid gap-2 rounded-xl border border-gray-100 p-2 dark:border-white/5 sm:grid-cols-[minmax(0,1fr)_120px_120px] sm:items-center">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-navy-700 dark:text-white">{{ $line->product?->name ?? 'Produk dihapus' }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400">
                                        order {{ $qtyText($line->qty_ordered) }} • diterima {{ $qtyText($line->qty_received) }} {{ $line->unit }}
                                    </p>
                                </div>
                                <p class="text-sm font-bold {{ $outstanding > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">{{ $qtyText($outstanding) }}</p>
                                <div class="flex items-center gap-1">
                                    <input type="number" step="0.001" min="0" max="{{ $outstanding }}"
                                           name="lines[{{ $line->id }}][qty]" x-model="rows[{{ $loop->index }}].qty"
                                           placeholder="0" class="h-9 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                    <span class="shrink-0 text-[10px] uppercase text-gray-400">{{ $line->unit }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="space-y-4 xl:sticky xl:top-6 xl:self-start">
                <div class="horizon-card p-5">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Total masuk</p>
                    <p class="mt-1 text-2xl font-bold text-navy-700 dark:text-white" x-text="total()">0</p>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400">unit sesuai satuan tiap produk</p>

                    <div class="mt-4 flex flex-col gap-2 border-t border-gray-100 pt-4 dark:border-white/5">
                        <button type="submit" class="flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                            <x-icon name="check-circle" class="h-4 w-4" />
                            Simpan penerimaan
                        </button>
                        <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-500 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/50 dark:hover:bg-white/5">
                            Batal
                        </a>
                    </div>

                    <p class="mt-3 text-[10px] leading-relaxed text-gray-400">
                        Setelah disimpan, stok di lokasi tujuan langsung bertambah dan order menutup sendiri begitu semua baris terpenuhi.
                    </p>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
