@php
    $navigation = [
        ['name' => 'Dashboard', 'href' => route('dashboard'), 'icon' => 'home', 'active' => request()->routeIs('dashboard')],
        ['name' => 'Profile', 'href' => route('profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
    ];
@endphp

<div
    class="duration-175 linear fixed !z-50 flex h-screen w-[293px] flex-col overflow-y-auto bg-white pb-6 shadow-2xl shadow-white/5 transition-all dark:!bg-navy-800 dark:text-white xl:!z-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-96 xl:translate-x-0'"
>
    <span
        class="absolute right-4 top-4 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-gray-500 hover:bg-lightPrimary dark:text-white/70 dark:hover:bg-white/10 xl:hidden"
        @click="sidebarOpen = false"
    >
        <x-icon name="close" class="h-4 w-4" />
    </span>

    <div class="mx-6 mt-7 flex items-center gap-3">
        <x-logo size="sm" />
        <div class="min-w-0">
            <p class="font-poppins text-[17px] font-bold uppercase leading-none tracking-wide text-navy-700 dark:text-white">
                Turbion
            </p>
            <p class="mt-1.5 text-[9px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-white/40">
                OIL Management System
            </p>
        </div>
    </div>

    <div class="mx-6 mb-4 mt-6 h-px bg-gray-200 dark:bg-white/10" />

    <p class="mb-2 px-8 text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-white/40">
        Menu
    </p>

    <ul class="space-y-1 px-4">
        @foreach ($navigation as $item)
            <li>
                <a
                    href="{{ $item['href'] }}"
                    class="relative flex items-center rounded-xl px-4 py-2.5 transition-colors {{ $item['active'] ? 'bg-lightPrimary text-navy-700 dark:bg-white/10 dark:text-white' : 'text-gray-600 hover:bg-lightPrimary hover:text-navy-700 dark:text-white/60 dark:hover:bg-white/5 dark:hover:text-white' }}"
                >
                    <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                    <span class="ml-3 truncate text-sm {{ $item['active'] ? 'font-bold' : 'font-medium' }}">
                        {{ $item['name'] }}
                    </span>
                    @if ($item['active'])
                        <span class="absolute right-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-full bg-brand-500 dark:bg-brand-400"></span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    @if ($activeCompany ?? null)
        <div class="mx-4 mt-auto rounded-2xl bg-gradient-to-br from-petrol-800 to-navy-900 p-4 text-white">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-white/40">Active company</p>
            <p class="mt-1 truncate text-sm font-bold">{{ $activeCompany->name }}</p>
            <p class="mt-0.5 truncate text-[11px] text-white/60">
                {{ $activeBranch?->name ?? (($activeMembership?->all_branches ?? true) ? 'All branches' : 'All assigned branches') }}
            </p>
            <a
                href="{{ route('context.select') }}"
                class="mt-3 flex items-center justify-center rounded-xl bg-white/10 py-2 text-xs font-bold text-white transition hover:bg-white/20"
            >
                Switch company
            </a>
        </div>
    @endif
</div>
