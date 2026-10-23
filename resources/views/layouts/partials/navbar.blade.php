@php
    $user = auth()->user();
    $navMemberships = $user?->companies()->with('branches')->get() ?? collect();
    $navAllowedBranchIds = $user?->branches()->pluck('branches.id') ?? collect();
    $navCompany = $activeCompany ?? null;
    $navBranch = $activeBranch ?? null;
@endphp

<nav class="sticky top-4 z-40 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-white/10 p-2 backdrop-blur-xl dark:bg-[#0b14374d]">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-white/50">
            <a href="{{ route('dashboard') }}" class="font-medium hover:text-navy-700 hover:underline dark:hover:text-white">
                Pages
            </a>
            <span>/</span>
            <span class="truncate">{{ $title ?? 'Dashboard' }}</span>
        </div>
        <p class="truncate text-2xl font-bold capitalize leading-tight text-navy-700 dark:text-white md:text-[30px]">
            <a href="{{ route('dashboard') }}" class="hover:text-navy-700 dark:hover:text-white">{{ $title ?? 'Dashboard' }}</a>
        </p>
    </div>

    <div class="flex w-full items-center gap-2 md:w-auto">
        @if ($navCompany)
            <div x-data="{ open: false }" class="relative min-w-0 flex-1 md:flex-none">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex w-full items-center gap-2.5 rounded-2xl bg-white py-2 pl-2.5 pr-3 text-left shadow-lg shadow-shadow-500/60 dark:bg-navy-800 dark:shadow-none md:w-auto"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-oil-500/10 text-oil-600 dark:bg-oil-500/15 dark:text-oil-300">
                        <x-icon name="document" class="h-4 w-4" />
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400">Company</span>
                        <span class="max-w-[150px] truncate text-xs font-bold text-navy-700 dark:text-white">{{ $navCompany->name }}</span>
                    </span>
                    <span class="ml-auto flex shrink-0 items-center gap-1 rounded-lg bg-oil-100 px-2 py-1 text-[10px] font-bold text-oil-700 dark:bg-oil-500/10 dark:text-oil-300">
                        <span class="max-w-[110px] truncate">{{ $navBranch?->name ?? (($activeMembership?->all_branches ?? true) ? 'All branches' : 'Assigned branches') }}</span>
                        <x-icon name="chevron-down" class="h-3 w-3" />
                    </span>
                </button>

                <div
                    x-cloak
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 top-[58px] z-50 max-h-[70vh] w-[19rem] max-w-[calc(100vw-1.5rem)] overflow-y-auto rounded-2xl bg-white p-2 shadow-xl shadow-shadow-500 dark:!bg-navy-700 dark:text-white dark:shadow-none"
                >
                    @foreach ($navMemberships as $company)
                        @php
                            $branches = $company->branches;

                            if (! $company->pivot->all_branches) {
                                $branches = $branches->whereIn('id', $navAllowedBranchIds);
                            }
                        @endphp

                        <p class="px-3 pb-1 pt-2 text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">
                            {{ $company->name }}
                        </p>

                        <form method="POST" action="{{ route('context.store') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            <button type="submit" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs font-semibold text-navy-700 transition hover:bg-lightPrimary dark:text-white dark:hover:bg-white/10">
                                {{ $company->pivot->all_branches ? 'All branches' : 'All assigned branches' }}
                                @if ($navCompany->id === $company->id && ! $navBranch)
                                    <x-icon name="check-circle" class="h-4 w-4 text-oil-500" />
                                @endif
                            </button>
                        </form>

                        @foreach ($branches as $branch)
                            <form method="POST" action="{{ route('context.store') }}">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $company->id }}">
                                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                <button type="submit" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs font-medium text-gray-600 transition hover:bg-lightPrimary dark:text-white/70 dark:hover:bg-white/10">
                                    {{ $branch->name }}
                                    @if ($navCompany->id === $company->id && $navBranch?->id === $branch->id)
                                        <x-icon name="check-circle" class="h-4 w-4 text-oil-500" />
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex shrink-0 items-center gap-0.5 rounded-full bg-white px-2 py-1.5 shadow-xl shadow-shadow-500 dark:!bg-navy-800 dark:shadow-none">
            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-600 hover:bg-lightPrimary dark:text-white dark:hover:bg-white/10 xl:hidden" @click="sidebarOpen = !sidebarOpen">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <div x-data="{ open: false }" class="relative">
                <button type="button" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-gray-600 hover:bg-lightPrimary dark:text-white dark:hover:bg-white/10" @click="open = !open">
                    <x-icon name="bell" class="h-4 w-4" />
                </button>

                <div
                    x-cloak
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 top-10 z-50 flex w-[320px] flex-col gap-3 rounded-[20px] bg-white p-4 shadow-xl shadow-shadow-500 dark:!bg-navy-700 dark:text-white dark:shadow-none sm:w-[420px]"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-base font-bold text-navy-700 dark:text-white">Notification</p>
                        <p class="text-sm font-bold text-navy-700 dark:text-white">Mark all read</p>
                    </div>

                    <button type="button" class="flex w-full items-center">
                        <div class="flex h-full w-[85px] items-center justify-center rounded-xl bg-gradient-to-b from-brandLinear to-brand-500 py-4 text-2xl text-white">
                            <x-icon name="arrow-up" class="h-5 w-5" />
                        </div>
                        <div class="ml-2 flex h-full w-full flex-col justify-center rounded-lg px-1 text-sm">
                            <p class="mb-1 text-left text-base font-bold text-gray-900 dark:text-white">New stock update received</p>
                            <p class="font-base text-left text-xs text-gray-900 dark:text-white">A new batch of oil products has been added to the inventory.</p>
                        </div>
                    </button>

                    <button type="button" class="flex w-full items-center">
                        <div class="flex h-full w-[85px] items-center justify-center rounded-xl bg-gradient-to-b from-brandLinear to-brand-500 py-4 text-2xl text-white">
                            <x-icon name="arrow-up" class="h-5 w-5" />
                        </div>
                        <div class="ml-2 flex h-full w-full flex-col justify-center rounded-lg px-1 text-sm">
                            <p class="mb-1 text-left text-base font-bold text-gray-900 dark:text-white">Monthly report is ready</p>
                            <p class="font-base text-left text-xs text-gray-900 dark:text-white">Your monthly sales &amp; stock report can be downloaded now.</p>
                        </div>
                    </button>
                </div>
            </div>

            <div x-data="{ open: false }" class="relative">
                <button type="button" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-gray-600 hover:bg-lightPrimary dark:text-white dark:hover:bg-white/10" @click="open = !open">
                    <x-icon name="info" class="h-4 w-4" />
                </button>

                <div
                    x-cloak
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 top-10 z-50 flex w-[280px] flex-col gap-2 rounded-[20px] bg-white p-4 shadow-xl shadow-shadow-500 dark:!bg-navy-700 dark:text-white dark:shadow-none"
                >
                    <div
                        style="background-image: url('{{ asset('images/layout/Navbar.png') }}'); background-repeat: no-repeat; background-size: cover;"
                        class="mb-2 aspect-video w-full rounded-lg"
                    ></div>
                    <span class="px-full linear flex cursor-pointer items-center justify-center rounded-xl bg-brand-500 py-[11px] font-bold text-white transition duration-200 hover:bg-brand-600 hover:text-white active:bg-brand-700 dark:bg-brand-400 dark:hover:bg-brand-300 dark:active:bg-brand-200">
                        Download now
                    </span>
                    <span class="px-full linear flex cursor-pointer items-center justify-center rounded-xl border py-[11px] font-bold text-navy-700 transition duration-200 hover:bg-gray-200 hover:text-navy-700 dark:!border-white/10 dark:text-white dark:hover:bg-white/20 dark:hover:text-white dark:active:bg-white/10">
                        See Documentation
                    </span>
                </div>
            </div>

            <button
                type="button"
                class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-gray-600 hover:bg-lightPrimary dark:text-white dark:hover:bg-white/10"
                @click="$store.darkMode.toggle()"
                :title="$store.darkMode.dark ? 'Light mode' : 'Dark mode'"
            >
                <x-icon name="moon" class="h-4 w-4" x-show="!$store.darkMode.dark" />
                <x-icon name="sun" class="h-4 w-4" x-show="$store.darkMode.dark" x-cloak />
            </button>

            <div x-data="{ open: false }" class="relative">
                <button type="button" class="ml-1 flex cursor-pointer items-center" @click="open = !open">
                    <img
                        src="{{ $user?->profile_photo_url ?? asset('images/avatars/avatar4.png') }}"
                        alt="{{ $user?->name ?? 'User' }}"
                        class="h-8 w-8 rounded-full object-cover"
                    >
                </button>

                <div
                    x-cloak
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 top-11 z-50 flex min-h-[12rem] w-56 flex-col justify-start rounded-[20px] bg-white pb-4 shadow-xl shadow-shadow-500 dark:!bg-navy-700 dark:text-white dark:shadow-none"
                >
                    <div class="ml-4 mt-3">
                        <p class="text-sm font-bold text-navy-700 dark:text-white">Hey, {{ $user?->name ?? 'Guest' }}</p>
                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-white/50">{{ $user?->username }}</p>
                    </div>

                    <div class="mt-3 h-px w-full bg-gray-200 dark:bg-white/20"></div>

                    <div class="ml-4 mt-3 flex flex-col">
                        <a href="{{ route('context.select') }}" class="text-sm text-gray-800 hover:text-gray-900 dark:text-white">
                            Switch Company
                        </a>
                        <a href="{{ route('profile.edit') }}" class="mt-3 text-sm text-gray-800 hover:text-gray-900 dark:text-white">
                            Profile Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="mt-3 text-sm font-medium text-red-500 hover:text-red-600">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
