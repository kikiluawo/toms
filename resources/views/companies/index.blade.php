<x-app-layout title="Master PT">
    <div class="mt-3 space-y-5">
        <div class="horizon-card p-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Struktur perusahaan</p>
            <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Daftar PT &amp; lokasi</h2>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-white/50">
                Hanya akun sistem yang bisa membuka halaman ini. PT nonaktif hilang dari seluruh aplikasi — data lamanya
                tetap tersimpan dan bisa dibuka lagi dengan mengaktifkan ulang.
            </p>
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

        <div class="horizon-card p-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">PT baru</p>
            <form method="POST" action="{{ route('companies.store') }}" class="mt-3 flex flex-col gap-3 md:flex-row">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="new-company-name" class="sr-only">Nama PT</label>
                    <input id="new-company-name" type="text" name="name" maxlength="255" placeholder="PT. Nama Perusahaan"
                           class="h-10 w-full rounded-xl border-gray-200 bg-white px-3.5 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                           required>
                    @error('name')
                        <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="w-full md:w-40">
                    <label for="new-company-code" class="sr-only">Kode PT</label>
                    <input id="new-company-code" type="text" name="code" maxlength="20" placeholder="KODE"
                           class="h-10 w-full rounded-xl border-gray-200 bg-white px-3.5 text-sm uppercase text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                           required>
                    @error('code')
                        <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat PT
                </button>
            </form>
        </div>

        @forelse ($companies as $company)
            <div class="horizon-card overflow-hidden">
                <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-oil-500/10 text-[11px] font-bold text-oil-600 dark:bg-oil-500/15 dark:text-oil-300">
                        {{ $company->code }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-navy-700 dark:text-white">{{ $company->name }}</p>
                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-white/50">
                            {{ $company->users_count }} anggota &middot; {{ $company->branches_count }} lokasi &middot;
                            {{ $company->departments_count }} department &middot; {{ $company->positions_count }} posisi
                        </p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide
                        {{ $company->is_active
                            ? 'bg-green-500/10 text-green-600 dark:text-green-400'
                            : 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-white/40' }}">
                        {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <form method="POST" action="{{ route('companies.destroy', $company) }}"
                          onsubmit="return confirm('Hapus {{ $company->name }} beserta seluruh lokasi dan kamusnya?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus PT">
                            <x-icon name="trash" class="h-4 w-4" />
                        </button>
                    </form>
                </div>

                <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                    <div class="space-y-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Identitas PT</p>
                        <form method="POST" action="{{ route('companies.update', $company) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="name" maxlength="255" value="{{ old('name', $company->name) }}"
                                   class="h-9 w-full rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                   required>
                            <div class="flex items-center gap-3">
                                <input type="text" name="code" maxlength="20" value="{{ old('code', $company->code) }}"
                                       class="h-9 w-28 rounded-xl border-gray-200 bg-white px-3 text-sm uppercase text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                       required>
                                <label class="flex items-center gap-2 text-xs font-semibold text-navy-700 dark:text-white">
                                    <input type="checkbox" name="is_active" value="1" @checked($company->is_active)
                                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-white/20 dark:bg-navy-900">
                                    Aktif di sistem
                                </label>
                                <button type="submit" class="ml-auto rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-navy-700 transition hover:bg-lightPrimary dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="space-y-3">
                        @php
                            $locationGroups = collect([
                                'Kantor Pusat' => $company->branches->filter(fn ($branch) => $branch->is_head_office),
                                'Cabang' => $company->branches->reject(fn ($branch) => $branch->is_head_office),
                            ])->reject(fn ($rows) => $rows->isEmpty());
                        @endphp

                        @forelse ($locationGroups as $locationLabel => $locationRows)
                            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">{{ $locationLabel }}</p>

                            @foreach ($locationRows as $branch)
                                <div class="flex items-center justify-between gap-2 rounded-xl border border-gray-100 px-3 py-2 dark:border-white/5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-navy-700 dark:text-white">{{ $branch->name }}</p>
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $branch->code }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('companies.branches.destroy', $branch) }}"
                                          onsubmit="return confirm('Hapus {{ $branch->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus lokasi">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        @empty
                            <p class="rounded-xl border border-dashed border-gray-200 px-3 py-4 text-center text-xs text-gray-400 dark:border-white/10">
                                Belum ada lokasi. Tandai satu sebagai kantor pusat supaya user pusat bisa dilampirkan.
                            </p>
                        @endforelse

                        <form method="POST" action="{{ route('companies.branches.store', $company) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="text" name="name" maxlength="255" placeholder="Nama lokasi"
                                   class="h-9 min-w-[10rem] flex-1 rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                   required>
                            <input type="text" name="code" maxlength="20" placeholder="KODE"
                                   class="h-9 w-24 rounded-xl border-gray-200 bg-white px-3 text-sm uppercase text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                   required>
                            <label class="flex items-center gap-1.5 text-[11px] font-semibold text-gray-500 dark:text-white/60">
                                <input type="checkbox" name="is_head_office" value="1"
                                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-white/20 dark:bg-navy-900">
                                Kantor pusat
                            </label>
                            <button type="submit" class="flex h-9 items-center gap-1.5 rounded-xl bg-brand-500 px-3 text-xs font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                Tambah
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="horizon-card p-8 text-center">
                <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada PT.</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Buat PT pertama di form di atas.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
