<x-app-layout title="Pemasok">
    @php
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-navy-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-white/10 dark:bg-navy-900 dark:text-white dark:placeholder:text-white/30';
        $labelClass = 'text-sm font-semibold text-navy-700 dark:text-white';

        $payload = $suppliers->map(fn ($supplier) => [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'code' => $supplier->code,
            'contact' => $supplier->contact,
            'phone' => $supplier->phone,
            'is_active' => $supplier->is_active,
        ])->values()->all();

        // A failed save should not wipe what the user just typed.
        $oldForm = [
            'name' => (string) old('name', ''),
            'code' => (string) old('code', ''),
            'contact' => (string) old('contact', ''),
            'phone' => (string) old('phone', ''),
            'is_active' => old('is_active', '1') === '1',
        ];
        $oldEditing = old('supplier_id') ? (int) old('supplier_id') : null;
    @endphp

    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Master data</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Pemasok</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-white/50">
                    Daftar ini dipakai semua PT saat membuat order. Pemasok yang sudah pernah diorder tidak bisa dihapus —
                    nonaktifkan saja supaya riwayat order tetap terbaca.
                </p>
            </div>
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-1.5 self-start text-xs font-bold text-brand-600 hover:text-brand-700 dark:text-brand-300">
                Katalog produk
                <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            </a>
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

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,340px)]"
             x-data='{
                 suppliers: {{ json_encode($payload, $jsonFlags) }},
                 editing: {{ json_encode($oldEditing, $jsonFlags) }},
                 form: {{ json_encode($oldForm, $jsonFlags) }},
                 storeUrl: {{ json_encode(route('suppliers.store'), $jsonFlags) }},
                 updateUrl: {{ json_encode(route('suppliers.update', ['supplier' => '__ID__']), $jsonFlags) }},
                 action() {
                     return this.editing ? this.updateUrl.replace("__ID__", this.editing) : this.storeUrl;
                 },
                 edit(id) {
                     const supplier = this.suppliers.find((item) => item.id === id);
                     if (!supplier) return;
                     this.editing = id;
                     this.form = {
                         name: supplier.name,
                         code: supplier.code || "",
                         contact: supplier.contact || "",
                         phone: supplier.phone || "",
                         is_active: supplier.is_active,
                     };
                     this.$nextTick(() => this.$el.querySelector("[name=name]").focus());
                 },
                 reset() {
                     this.editing = null;
                     this.form = { name: "", code: "", contact: "", phone: "", is_active: true };
                 },
             }'>
            <div class="horizon-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <p class="text-sm font-bold text-navy-700 dark:text-white">Pemasok terdaftar ({{ $suppliers->count() }})</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px]">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Pemasok</th>
                                <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">PIC</th>
                                <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Order</th>
                                <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($suppliers as $supplier)
                                <tr class="align-top transition hover:bg-lightPrimary/60 dark:hover:bg-white/5">
                                    <td class="px-5 py-4">
                                        <p class="flex items-center gap-2 text-sm font-bold text-navy-700 dark:text-white">
                                            {{ $supplier->name }}
                                            @unless ($supplier->is_active)
                                                <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-white/40">Nonaktif</span>
                                            @endunless
                                        </p>
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $supplier->code ?: 'tanpa kode' }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm text-gray-600 dark:text-white/60">{{ $supplier->contact ?: '—' }}</p>
                                        <p class="text-xs text-gray-400">{{ $supplier->phone ?: '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-end text-sm font-semibold text-navy-700 dark:text-white">
                                        {{ $supplier->purchase_orders_count }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($userContext->isGroup())
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" @click="edit({{ $supplier->id }})"
                                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-lightPrimary hover:text-navy-700 dark:text-white/60 dark:hover:bg-white/10 dark:hover:text-white" title="Ubah">
                                                    <x-icon name="pencil" class="h-4 w-4" />
                                                </button>
                                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                                                      onsubmit="return confirm('Hapus pemasok {{ $supplier->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-red-50 hover:text-red-500 dark:text-white/60 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus">
                                                        <x-icon name="trash" class="h-4 w-4" />
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <p class="text-end text-xs text-gray-400">hanya baca</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center">
                                        <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada pemasok.</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Tambahkan pemasok sebelum COO bisa membuat order.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($userContext->isGroup())
                <div class="horizon-card self-start p-5">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400" x-text="editing ? 'Ubah pemasok' : 'Tambah pemasok'"></p>

                    <form method="POST" :action="action()" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" :disabled="!editing">
                        <input type="hidden" name="supplier_id" :value="editing || ''">

                        <div>
                            <label class="{{ $labelClass }}">Nama pemasok</label>
                            <input type="text" name="name" maxlength="120" x-model="form.name" placeholder="mis. PT. Patra Niaga"
                                   class="{{ $inputClass }}" required>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Kode</label>
                            <input type="text" name="code" maxlength="20" x-model="form.code" pattern="[A-Za-z0-9_-]+" placeholder="mis. PN"
                                   class="{{ $inputClass }} uppercase">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Nama PIC</label>
                            <input type="text" name="contact" maxlength="80" x-model="form.contact" placeholder="mis. Budi — bagian penjualan"
                                   class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Telepon</label>
                            <input type="text" name="phone" maxlength="30" x-model="form.phone" placeholder="mis. 0778-123456"
                                   class="{{ $inputClass }}">
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-white/60">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                   class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-white/20 dark:bg-navy-900">
                            Aktif — boleh dipakai pada order baru
                        </label>

                        <div class="flex items-center gap-2 pt-1">
                            <button type="submit" class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                <span x-text="editing ? 'Simpan perubahan' : 'Simpan pemasok'"></span>
                            </button>
                            <button type="button" x-show="editing" x-cloak @click="reset()"
                                    class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm font-bold text-gray-500 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/50 dark:hover:bg-white/5">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="horizon-card self-start p-5 text-center">
                    <p class="text-sm font-semibold text-navy-700 dark:text-white">Daftar pemasok hanya bisa diubah level grup</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Kamu tetap bisa memakai daftar ini untuk membuat order.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
