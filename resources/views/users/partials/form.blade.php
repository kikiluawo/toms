@php
    $inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-navy-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-white/10 dark:bg-navy-900 dark:text-white dark:placeholder:text-white/30';
    $labelClass = 'text-sm font-semibold text-navy-700 dark:text-white';
    $checkClass = 'h-4 w-4 shrink-0 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-white/20 dark:bg-navy-900';
    $scopeLabels = config('user.scopes');
    $checkedBranches = array_map('intval', (array) old('branches', $selectedBranches));

    $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    $roleScopeMap = collect($roleOptions)->map(fn ($role) => $role['scope'])->all();
    $initialPts = collect($companies)
        ->mapWithKeys(fn ($company) => [
            $company->id => (bool) old('companies.'.$company->id.'.selected', isset($selectedCompanies[$company->id])),
        ])
        ->all();
    $initial = [
        'name' => old('name', $user->name),
        'username' => old('username', $user->username),
    ];
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
        name: {{ json_encode($initial['name'], $jsonFlags) }},
        username: {{ json_encode($initial['username'], $jsonFlags) }},
        pts: {{ json_encode($initialPts, $jsonFlags) }},
        roleScope: {{ json_encode($roleScopeMap, $jsonFlags) }},
        scopeLabels: {{ json_encode($scopeLabels, $jsonFlags) }},
        initials() {
            if (!this.name) return "NU";
            return this.name.trim().split(/\s+/).slice(0, 2).map((word) => word.charAt(0)).join("").toUpperCase();
        },
        scopeOf(role) {
            return this.roleScope[role] || "";
        },
        enabledCount() {
            return Object.values(this.pts).filter((value) => value).length;
        },
    }'
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-navy-800">
                <div class="flex items-start gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500/10 text-brand-500 dark:bg-brand-400/10 dark:text-brand-400">
                        <x-icon name="user" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-500 dark:text-brand-400">Langkah 01</p>
                        <h3 class="mt-1 text-base font-bold text-navy-700 dark:text-white">Informasi akun</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">Username digunakan untuk login. Email harus aktif dan unik.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                    <div>
                        <label for="name" class="{{ $labelClass }}">Nama lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" x-model="name" class="{{ $inputClass }}" placeholder="Masukkan nama lengkap">
                        @error('name') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="username" class="{{ $labelClass }}">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required autocomplete="username" x-model="username" class="{{ $inputClass }}" placeholder="contoh: budi.marketing">
                        @error('username') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="{{ $labelClass }}">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="{{ $inputClass }}" placeholder="nama@perusahaan.com">
                        @error('email') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="{{ $labelClass }}">
                            Password
                            @if ($user->exists)
                                <span class="font-normal text-gray-400">(opsional)</span>
                            @endif
                        </label>
                        <input id="password" name="password" type="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" class="{{ $inputClass }}" placeholder="Minimal 8 karakter">
                        @error('password') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="{{ $labelClass }}">Konfirmasi password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" class="{{ $inputClass }}" placeholder="Ulangi password">
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-navy-800">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-6 py-5 dark:border-white/5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-petrol-500/10 text-petrol-600 dark:bg-petrol-400/10 dark:text-petrol-300">
                            <x-icon name="settings" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-petrol-600 dark:text-petrol-300">Langkah 02</p>
                            <h3 class="mt-1 text-base font-bold text-navy-700 dark:text-white">Penempatan &amp; peran per PT</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">Role, department, dan posisi disimpan untuk tiap PT secara terpisah.</p>
                        </div>
                    </div>
                    <span class="rounded-full border border-gray-200 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400 dark:border-white/10 dark:text-white/40">Bisa lebih dari satu PT</span>
                </div>

                <div class="space-y-3 p-6">
                    <p class="rounded-xl bg-lightPrimary px-4 py-3 text-[11px] leading-relaxed text-gray-500 dark:bg-white/5 dark:text-white/50">
                        CEO, CFO, dan COO berlaku di <span class="font-semibold text-navy-700 dark:text-white">seluruh PT aktif</span>. PT yang dicentang untuk peran tersebut
                        hanyalah PT penempatan sebagai titik terdaftar orang ini — bukan batas aksesnya.
                    </p>

                    @forelse ($companies as $company)
                        @php
                            $state = $selectedCompanies[$company->id] ?? null;
                            $dictionary = $dictionaries[$company->id] ?? ['departments' => [], 'positions' => []];
                            $initialRole = old('companies.'.$company->id.'.role', $state['role'] ?? '');
                            $initialDepartment = (int) old('companies.'.$company->id.'.department_id', $state['department_id'] ?? 0);
                            $initialPosition = (int) old('companies.'.$company->id.'.position_id', $state['position_id'] ?? 0);
                        @endphp

                        <div
                            x-data="{ role: {{ json_encode($initialRole, $jsonFlags) }} }"
                            class="rounded-2xl border transition"
                            :class="pts[{{ $company->id }}] ? 'border-brand-500/30 bg-brand-500/[0.03] dark:border-brand-400/25 dark:bg-brand-400/[0.04]' : 'border-gray-200 dark:border-white/10'"
                        >
                            <div class="flex items-center justify-between gap-4 p-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-petrol-500/10 text-[11px] font-bold tracking-wide text-petrol-600 dark:bg-petrol-400/10 dark:text-petrol-300">
                                        {{ $company->code }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-navy-700 dark:text-white">{{ $company->name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">{{ $company->branches->count() }} cabang tersedia</p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="hidden text-[10px] font-bold uppercase tracking-[0.14em] sm:inline" :class="pts[{{ $company->id }}] ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-white/40'" x-text="pts[{{ $company->id }}] ? 'Aktif' : 'Nonaktif'"></span>
                                    <input type="checkbox" name="companies[{{ $company->id }}][selected]" value="1" x-model="pts[{{ $company->id }}]" class="sr-only">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="pts[{{ $company->id }}]"
                                        @click="pts[{{ $company->id }}] = !pts[{{ $company->id }}]"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/20"
                                        :class="pts[{{ $company->id }}] ? 'bg-brand-500' : 'bg-gray-200 dark:bg-white/15'"
                                    >
                                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition" :class="pts[{{ $company->id }}] ? 'translate-x-5' : 'translate-x-1'"></span>
                                    </button>
                                </div>
                            </div>

                            <div x-show="pts[{{ $company->id }}]" x-cloak class="border-t border-gray-200/70 p-4 dark:border-white/10">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label for="company-role-{{ $company->id }}" class="{{ $labelClass }}">Role di PT ini</label>
                                        <select
                                            id="company-role-{{ $company->id }}"
                                            name="companies[{{ $company->id }}][role]"
                                            class="{{ $inputClass }}"
                                            x-model="role"
                                        >
                                            <option value="">Pilih role</option>
                                            @foreach ($roleOptions as $value => $role)
                                                <option value="{{ $value }}" @selected($initialRole === $value)>{{ $role['label'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('companies.'.$company->id.'.role') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="company-department-{{ $company->id }}" class="{{ $labelClass }}">Department</label>
                                        <select
                                            id="company-department-{{ $company->id }}"
                                            name="companies[{{ $company->id }}][department_id]"
                                            class="{{ $inputClass }}"
                                        >
                                            <option value="">Pilih department</option>
                                            @foreach ($dictionary['departments'] as $department)
                                                <option value="{{ $department->id }}" @selected($initialDepartment === $department->id)>{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('companies.'.$company->id.'.department') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                        @error('companies.'.$company->id.'.department_id') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="company-position-{{ $company->id }}" class="{{ $labelClass }}">Posisi / jabatan</label>
                                        <select
                                            id="company-position-{{ $company->id }}"
                                            name="companies[{{ $company->id }}][position_id]"
                                            class="{{ $inputClass }}"
                                        >
                                            <option value="">Pilih posisi</option>
                                            @foreach ($dictionary['positions'] as $position)
                                                <option value="{{ $position->id }}" @selected($initialPosition === $position->id)>{{ $position->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('companies.'.$company->id.'.position') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                        @error('companies.'.$company->id.'.position_id') <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="mt-3 flex items-start gap-2.5 rounded-xl bg-lightPrimary px-4 py-3 text-xs text-gray-500 dark:bg-white/5 dark:text-white/50">
                                    <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-oil-600 dark:text-oil-300" />
                                    <p>
                                        Jangkauan data:
                                        <span class="font-semibold text-navy-700 dark:text-white" x-text="scopeLabels[scopeOf(role)] || 'pilih role dahulu'"></span>
                                    </p>
                                </div>

                                <div x-show="scopeOf(role) === 'branch'" x-cloak class="mt-4">
                                    @php
                                        $locationGroups = collect([
                                            'Kantor Pusat' => $company->branches->filter(fn ($branch) => $branch->is_head_office),
                                            'Cabang' => $company->branches->reject(fn ($branch) => $branch->is_head_office),
                                        ])->reject(fn ($rows) => $rows->isEmpty());
                                    @endphp

                                    @if ($locationGroups->isNotEmpty())
                                        <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-400">Pilih lokasi yang boleh diakses</p>
                                        @foreach ($locationGroups as $locationLabel => $locationRows)
                                            <p class="mb-2 mt-3 text-[10px] font-semibold uppercase tracking-wide text-oil-600 dark:text-oil-300">{{ $locationLabel }}</p>
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                @foreach ($locationRows as $branch)
                                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-gray-200 px-3 py-2.5 text-sm transition hover:border-brand-400/60 has-[:checked]:border-brand-500/50 has-[:checked]:bg-brand-500/5 dark:border-white/10 dark:hover:border-brand-400/40">
                                                        <input
                                                            type="checkbox"
                                                            name="branches[]"
                                                            value="{{ $branch->id }}"
                                                            @checked(in_array($branch->id, $checkedBranches, true))
                                                            class="{{ $checkClass }}"
                                                        >
                                                        <span class="text-navy-700 dark:text-white">{{ $branch->name }}</span>
                                                        <span class="ml-auto text-[10px] uppercase tracking-wide text-gray-400">{{ $branch->code }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-white/50">PT ini belum punya lokasi, jadi user ini bekerja langsung untuk PT tersebut. Saat lokasi pertama dibuat, dia otomatis ditugaskan ke sana.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 p-8 text-center dark:border-white/15">
                            <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada PT yang bisa diatur.</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Hubungi administrator untuk menambahkan akses PT.</p>
                        </div>
                    @endforelse

                    @error('companies') <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                    @error('branches') <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-navy-800">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400">Pratinjau profil</p>
                    <span class="flex h-2 w-2 rounded-full bg-green-400 shadow-[0_0_0_4px_rgba(74,222,128,0.15)]"></span>
                </div>

                <div class="mt-5 flex items-center gap-3.5">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 text-lg font-bold text-white shadow-lg shadow-brand-500/20" x-text="initials()"></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-navy-700 dark:text-white" x-text="name || 'Nama user'"></p>
                        <p class="truncate text-xs text-gray-500 dark:text-white/50"><span class="text-gray-400">&#64;</span><span x-text="username || 'username'"></span></p>
                    </div>
                </div>

                <div class="mt-5 space-y-3 border-t border-gray-100 pt-4 text-xs dark:border-white/5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-500 dark:text-white/50">PT aktif</span>
                        <span class="rounded-full bg-brand-500/10 px-2.5 py-1 text-[10px] font-bold tracking-wide text-brand-500 dark:bg-brand-400/10 dark:text-brand-400" x-text="enabledCount() + ' PT'"></span>
                    </div>
                    <p class="rounded-xl bg-lightPrimary px-3 py-2.5 text-[11px] leading-relaxed text-gray-500 dark:bg-white/5 dark:text-white/50">
                        Tidak ada role global. Setiap PT punya role, department, dan posisinya sendiri.
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-navy-800">
                <div class="border-b border-gray-100 px-6 py-5 dark:border-white/5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-petrol-600 dark:text-petrol-300">Langkah 03</p>
                    <h3 class="mt-1 text-base font-bold text-navy-700 dark:text-white">Hak akses menu</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-white/50">Menu yang tampil di sidebar pengguna.</p>
                </div>

                <div class="space-y-2 p-5">
                    @foreach ($menuItems as $key => $item)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 transition hover:border-brand-400/50 has-[:checked]:border-brand-500/40 has-[:checked]:bg-brand-500/5 dark:border-white/10 dark:hover:border-brand-400/30">
                            <input type="checkbox" name="menus[]" value="{{ $key }}" @checked(in_array($key, $selectedMenus, true)) class="{{ $checkClass }} mt-0.5">
                            <span class="min-w-0">
                                <span class="flex items-center gap-1.5 text-sm font-bold text-navy-700 dark:text-white">
                                    <x-icon :name="$item['icon']" class="h-4 w-4 text-gray-400" />
                                    {{ $item['label'] }}
                                </span>
                                @if (! empty($item['description']))
                                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-white/50">{{ $item['description'] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach

                    @error('menus') <p class="text-xs font-medium text-red-500">{{ $message }}</p> @enderror

                    <p class="rounded-xl bg-lightPrimary px-3 py-2.5 text-[11px] leading-relaxed text-gray-500 dark:bg-white/5 dark:text-white/50">
                        Dashboard dan Profile selalu bisa diakses. Menu lain mengikuti pilihan di atas.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-navy-800">
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-600 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/20 dark:bg-brand-400 dark:hover:bg-brand-300">
                    <x-icon name="check-circle" class="h-4 w-4" />
                    {{ $submitLabel }}
                </button>
                <a href="{{ route('users.index') }}" class="mt-2 flex w-full items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-navy-700 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white dark:hover:bg-white/5">
                    Batal
                </a>
                <p class="mt-3 text-center text-[11px] text-gray-400 dark:text-white/30">Data dapat diubah kembali setelah disimpan.</p>
            </section>
        </aside>
    </div>
</form>
