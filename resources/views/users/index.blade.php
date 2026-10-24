<x-app-layout title="Users">
    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Access control</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Users &amp; permissions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                    {{ $users->total() }} user terdaftar. Atur akses PT/cabang dan menu yang bisa dibuka.
                </p>
            </div>
            <a href="{{ route('users.create') }}" class="flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-600 dark:bg-brand-400 dark:hover:bg-brand-300">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah user
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

        <div class="horizon-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">User</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Akses PT</th>
                            <th class="px-5 py-3 text-start text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Menu</th>
                            <th class="px-5 py-3 text-end text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($users as $member)
                            <tr class="align-top transition hover:bg-lightPrimary/60 dark:hover:bg-white/5">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500/10 text-xs font-bold text-brand-500 dark:bg-brand-400/10 dark:text-brand-400">
                                            {{ strtoupper(mb_substr($member->name, 0, 2)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-1.5 text-sm font-bold text-navy-700 dark:text-white">
                                                {{ $member->name }}
                                                @if ($member->is(auth()->user()))
                                                    <span class="rounded-md bg-oil-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-oil-700 dark:bg-oil-500/10 dark:text-oil-300">You</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-white/50">&#64;{{ $member->username }}</p>
                                            <p class="truncate text-xs text-gray-400">{{ $member->email }}</p>
                                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                                {{ $member->companies->map(fn ($company) => config('user.roles.'.$company->pivot->role.'.label') ?? strtoupper((string) $company->pivot->role))->unique()->filter()->join(' / ') ?: 'Tanpa role' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1.5">
                                        @forelse ($member->companies as $company)
                                            @php
                                                $membership = $membershipMap[$member->id][$company->id] ?? null;
                                                $branchNames = $member->branches->where('company_id', $company->id)->pluck('name');
                                                $scope = $membership?->data_scope ?? 'branch';
                                            @endphp
                                            <div>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="rounded-md bg-petrol-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-petrol-600 dark:bg-petrol-400/10 dark:text-petrol-300">
                                                        {{ $company->code }}
                                                    </span>
                                                    <span class="text-xs font-semibold text-navy-700 dark:text-white">{{ $company->name }}</span>
                                                    <span class="rounded-md bg-brand-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-600 dark:bg-brand-400/10 dark:text-brand-300">
                                                        {{ config('user.roles.'.$membership?->role.'.label') ?? '—' }}
                                                    </span>
                                                    @if ($scope === 'group')
                                                        <span class="rounded-md bg-oil-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-oil-700 dark:bg-oil-500/10 dark:text-oil-300">
                                                            Seluruh PT
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="mt-0.5 text-[10px] text-gray-400">
                                                    {{ $membership?->department?->name ?: '—' }} • {{ $membership?->position?->name ?: '—' }} •
                                                    {{ config('user.scopes.'.$scope) ?? 'Tidak ada akses' }}
                                                    @if ($scope === 'branch' && $branchNames->isNotEmpty())
                                                        ({{ $branchNames->join(', ') }})
                                                    @endif
                                                </p>
                                            </div>
                                        @empty
                                            <span class="text-xs text-gray-400">Belum ada PT</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($menuItems as $key => $item)
                                            @if ($member->hasMenuAccess($key))
                                                <span class="flex items-center gap-1 rounded-md bg-green-500/10 px-1.5 py-1 text-[10px] font-bold text-green-600 dark:text-green-400">
                                                    <x-icon name="check-circle" class="h-3 w-3" />
                                                    {{ $item['label'] }}
                                                </span>
                                            @else
                                                <span class="flex items-center gap-1 rounded-md bg-gray-100 px-1.5 py-1 text-[10px] font-semibold text-gray-400 dark:bg-white/5 dark:text-white/30">
                                                    <x-icon name="lock" class="h-3 w-3" />
                                                    {{ $item['label'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('users.edit', $member) }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-lightPrimary hover:text-navy-700 dark:text-white/60 dark:hover:bg-white/10 dark:hover:text-white" title="Edit user">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        @unless ($member->is(auth()->user()))
                                            <form method="POST" action="{{ route('users.destroy', $member) }}" onsubmit="return confirm('Hapus user {{ $member->name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-red-50 hover:text-red-500 dark:text-white/60 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Hapus user">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <p class="text-sm font-semibold text-navy-700 dark:text-white">Belum ada user.</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-white/50">Tambahkan user pertama untuk mulai mengatur akses.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-100 p-4 dark:border-white/5">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
