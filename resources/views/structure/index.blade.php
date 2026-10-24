<x-app-layout title="Struktur">
    @php
        $targets = collect();

        if ($userContext->isGroup()) {
            $targets->push([
                'tag' => 'group',
                'company_id' => null,
                'name' => 'Kamus bersama',
                'code' => 'ALL',
                'hint' => 'Department & posisi ini tersedia di semua PT.',
                'editable' => true,
            ]);
        }

        foreach ($companies as $company) {
            $targets->push([
                'tag' => (string) $company->id,
                'company_id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'hint' => $userContext->canActOn($company->id)
                    ? 'Hanya berlaku di '. $company->name.'.'
                    : 'Hanya bisa dibaca — wewenangmu di PT ini sebatas cabang.',
                'editable' => $userContext->canActOn($company->id),
            ]);
        }
    @endphp

    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Master data</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Department &amp; posisi</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-white/50">
                    Kamus ini yang muncul di form user saat menetapkan jabatan per PT. Tiap PT punya daftarnya sendiri;
                    kamus bersama bisa dipakai lintas PT.
                </p>
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

        @error('company_id')
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                {{ $message }}
            </div>
        @enderror

        @forelse ($targets as $target)
            @php
                $tag = $target['tag'];
                $panelDepartments = $departments->get($tag, collect());
                $panelPositions = $positions->get($tag, collect());
                $departmentOptions = $target['company_id'] === null
                    ? $departments->get('group', collect())
                    : $departments->get('group', collect())->concat($departments->get($target['company_id'], collect()));
            @endphp

            <div class="horizon-card overflow-hidden">
                <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-oil-500/10 text-oil-600 dark:bg-oil-500/15 dark:text-oil-300">
                        <x-icon name="{{ $target['company_id'] === null ? 'shield' : 'building' }}" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 text-sm font-bold text-navy-700 dark:text-white">
                            {{ $target['name'] }}
                            <span class="rounded-md bg-petrol-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-petrol-600 dark:bg-petrol-400/10 dark:text-petrol-300">
                                {{ $target['code'] }}
                            </span>
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">{{ $target['hint'] }}</p>
                    </div>
                </div>

                <div class="grid gap-5 p-5 lg:grid-cols-2">
                    {{-- Departments --}}
                    <div class="space-y-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">
                            Department ({{ $panelDepartments->count() }})
                        </p>

                        @error('department_' . $tag . '_name')
                            <p class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-600 dark:bg-red-500/10 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @forelse ($panelDepartments as $department)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-gray-100 px-3 py-2 dark:border-white/5">
                                <span class="truncate text-sm font-semibold text-navy-700 dark:text-white">{{ $department->name }}</span>
                                @if ($target['editable'])
                                    <form method="POST" action="{{ route('structure.departments.destroy', $department) }}"
                                          onsubmit="return confirm('Hapus department {{ $department->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-gray-200 px-3 py-4 text-center text-xs text-gray-400 dark:border-white/10">
                                Belum ada department.
                            </p>
                        @endforelse

                        @if ($target['editable'])
                            <form method="POST" action="{{ route('structure.departments.store') }}" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $target['company_id'] }}">
                                <input type="text" name="name" maxlength="60" placeholder="Nama department"
                                       class="h-9 min-w-0 flex-1 rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                       required>
                                <button type="submit" class="flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-brand-500 px-3 text-xs font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                                    <x-icon name="plus" class="h-3.5 w-3.5" />
                                    Tambah
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Positions --}}
                    <div class="space-y-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">
                            Posisi ({{ $panelPositions->count() }})
                        </p>

                        @error('position_' . $tag . '_name')
                            <p class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-600 dark:bg-red-500/10 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('position_' . $tag . '_department_id')
                            <p class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-600 dark:bg-red-500/10 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @forelse ($panelPositions as $position)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-gray-100 px-3 py-2 dark:border-white/5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-navy-700 dark:text-white">{{ $position->name }}</p>
                                    <p class="truncate text-[10px] uppercase tracking-wide text-gray-400">{{ $position->department?->name ?? 'Lintas department' }}</p>
                                </div>
                                @if ($target['editable'])
                                    <form method="POST" action="{{ route('structure.positions.destroy', $position) }}"
                                          onsubmit="return confirm('Hapus posisi {{ $position->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-gray-200 px-3 py-4 text-center text-xs text-gray-400 dark:border-white/10">
                                Belum ada posisi.
                            </p>
                        @endforelse

                        @if ($target['editable'])
                            <form method="POST" action="{{ route('structure.positions.store') }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $target['company_id'] }}">
                                <div class="flex items-center gap-2">
                                    <input type="text" name="name" maxlength="80" placeholder="Nama posisi"
                                           class="h-9 min-w-0 flex-1 rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                           required>
                                    <button type="submit" class="flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-brand-500 px-3 text-xs font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                                        <x-icon name="plus" class="h-3.5 w-3.5" />
                                        Tambah
                                    </button>
                                </div>
                                <select name="department_id"
                                        class="h-9 w-full rounded-xl border-gray-200 bg-white px-3 text-sm text-navy-700 focus:border-brand-500 focus:ring-brand-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                    <option value="">Tanpa department</option>
                                    @foreach ($departmentOptions as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="horizon-card p-8 text-center">
                <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada PT aktif.</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Tambahkan PT sebelum menata kamus department dan posisi.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
