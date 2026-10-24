<x-app-layout title="Katalog">
    @php
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-navy-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-white/10 dark:bg-navy-900 dark:text-white dark:placeholder:text-white/30';
        $labelClass = 'text-sm font-semibold text-navy-700 dark:text-white';
        $all = $products->flatten();

        $payload = $all->map(fn ($product) => [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'unit' => $product->unit,
        ])->values()->all();

        // A failed save should not wipe what the user just typed.
        $oldForm = [
            'code' => (string) old('code', ''),
            'name' => (string) old('name', ''),
            'category' => (string) old('category', 'solar'),
            'unit' => (string) old('unit', 'liter'),
        ];
        $oldEditing = old('product_id') ? (int) old('product_id') : null;
    @endphp

    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Master data</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Katalog produk minyak</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-white/50">
                    Satu katalog untuk seluruh PT. Produk yang sudah tidak dijual lagi cukup dinonaktifkan —
                    riwayat order dan stoknya tetap terbaca.
                </p>
            </div>
            <a href="{{ route('suppliers.index') }}" class="flex items-center gap-1.5 self-start text-xs font-bold text-brand-600 hover:text-brand-700 dark:text-brand-300">
                Kelola pemasok
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
                 products: {{ json_encode($payload, $jsonFlags) }},
                 editing: {{ json_encode($oldEditing, $jsonFlags) }},
                 form: {{ json_encode($oldForm, $jsonFlags) }},
                 storeUrl: {{ json_encode(route('catalog.store'), $jsonFlags) }},
                 updateUrl: {{ json_encode(route('catalog.update', ['product' => '__ID__']), $jsonFlags) }},
                 action() {
                     return this.editing ? this.updateUrl.replace("__ID__", this.editing) : this.storeUrl;
                 },
                 edit(id) {
                     const product = this.products.find((item) => item.id === id);
                     if (!product) return;
                     this.editing = id;
                     this.form = {
                         code: product.code,
                         name: product.name,
                         category: product.category,
                         unit: product.unit,
                     };
                     this.$nextTick(() => this.$el.querySelector("[name=name]").focus());
                 },
                 reset() {
                     this.editing = null;
                     this.form = { code: "", name: "", category: "solar", unit: "liter" };
                 },
             }'>
            <div class="horizon-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <p class="text-sm font-bold text-navy-700 dark:text-white">Produk ({{ $all->count() }})</p>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $all->where('is_active', false)->count() }} nonaktif</p>
                </div>

                <div class="space-y-5 p-5">
                    @forelse (config('oil.categories') as $value => $label)
                        @php $group = $products->get($value, collect()); @endphp
                        @if ($group->isNotEmpty())
                            <div class="space-y-2">
                                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">{{ $label }}</p>
                                @foreach ($group as $product)
                                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 px-3 py-2 dark:border-white/5 {{ $product->is_active ? '' : 'opacity-60' }}">
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-2 truncate text-sm font-semibold text-navy-700 dark:text-white">
                                                {{ $product->name }}
                                                @unless ($product->is_active)
                                                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-white/40">Nonaktif</span>
                                                @endunless
                                            </p>
                                            <p class="truncate text-[10px] uppercase tracking-wide text-gray-400">
                                                {{ $product->code }} • satuan baku {{ $product->unit_label }}
                                            </p>
                                        </div>

                                        @if ($userContext->isGroup())
                                            <div class="flex items-center gap-1">
                                                <button type="button" @click="edit({{ $product->id }})" @if ($oldEditing === $product->id) x-init="edit({{ $product->id }})" @endif
                                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-lightPrimary hover:text-navy-700 dark:hover:bg-white/10 dark:hover:text-white" title="Ubah produk">
                                                    <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                </button>

                                                <form method="POST" action="{{ route('catalog.active', $product) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10 dark:hover:text-amber-400"
                                                            title="{{ $product->is_active ? 'Nonaktifkan (sudah tidak dijual)' : 'Aktifkan lagi' }}">
                                                        <x-icon name="{{ $product->is_active ? 'eye-off' : 'check-circle' }}" class="h-3.5 w-3.5" />
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('catalog.destroy', $product) }}"
                                                      onsubmit="return confirm('Hapus produk {{ $product->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus">
                                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-200 px-3 py-6 text-center text-xs text-gray-400 dark:border-white/10">
                            Katalog masih kosong. Tambahkan produk pertama di kanan.
                        </p>
                    @endforelse
                </div>
            </div>

            @if ($userContext->isGroup())
                <div class="horizon-card self-start p-5">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400" x-text="editing ? 'Ubah produk' : 'Tambah produk'"></p>

                    <form method="POST" :action="action()" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" :disabled="!editing">
                        <input type="hidden" name="product_id" :value="editing || ''">

                        <div>
                            <label class="{{ $labelClass }}">Nama produk</label>
                            <input type="text" name="name" maxlength="120" x-model="form.name" placeholder="mis. Turbion Diesel Oil (TDO)"
                                   class="{{ $inputClass }}" required>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Kode</label>
                            <input type="text" name="code" maxlength="20" x-model="form.code" pattern="[A-Za-z0-9_-]+" placeholder="mis. TDO"
                                   class="{{ $inputClass }} uppercase" required>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Kategori</label>
                            <select name="category" x-model="form.category" class="{{ $inputClass }}">
                                @foreach (config('oil.categories') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Satuan baku</label>
                            <select name="unit" x-model="form.unit" class="{{ $inputClass }}">
                                @foreach (config('oil.units') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[10px] leading-relaxed text-gray-400">
                                Semua pencatatan stok produk ini memakai satuan ini. Sudah punya order/stok? Satuan terkunci.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <button type="submit" class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                <span x-text="editing ? 'Simpan perubahan' : 'Simpan produk'"></span>
                            </button>
                            <button type="button" x-show="editing" x-cloak @click="reset()"
                                    class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm font-bold text-gray-500 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/50 dark:hover:bg-white/5">
                                Batal
                            </button>
                        </div>

                        <p class="text-[10px] leading-relaxed text-gray-400" x-show="!editing" x-cloak>
                            Produk aktif otomatis muncul di form order semua PT.
                        </p>
                    </form>
                </div>
            @else
                <div class="horizon-card self-start p-5 text-center">
                    <p class="text-sm font-semibold text-navy-700 dark:text-white">Katalog hanya bisa diubah level grup</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Kamu boleh memakai produk ini untuk order, tapi tidak menambahnya.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
