<x-guest-layout title="Select Company">
    <h3 class="text-3xl font-bold tracking-tight text-petrol-900 sm:text-4xl dark:text-white">
        Select Company
    </h3>
    <p class="mt-2 text-sm text-gray-600 dark:text-white/50">
        Choose the company and branch you want to manage. You can switch anytime.
    </p>

    <div class="mt-8 space-y-4">
        @if ($userContext->isGroup())
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-oil-500/30 bg-oil-500/5 p-4">
                <div>
                    <p class="text-sm font-bold text-petrol-900 dark:text-white">Seluruh PT</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">
                        {{ $userContext->roleLabel() }} &middot; data lintas perusahaan
                    </p>
                </div>
                <form method="POST" action="{{ route('context.store') }}">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ \App\Support\UserContext::ALL }}">
                    <button
                        type="submit"
                        class="rounded-xl bg-gradient-to-r from-oil-500 to-oil-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-oil-600/20 transition hover:from-oil-600 hover:to-oil-700"
                    >
                        Buka semua PT
                    </button>
                </form>
            </div>
        @endif

        @forelse ($picker as $row)
            @php
                $company = $row['company'];
                $isActive = $activeCompanyId == $company->id;
            @endphp

            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-petrol-900 dark:text-white">{{ $company->name }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">
                            {{ $company->code }}
                            @if ($row['role_label'])
                                &middot; {{ $row['role_label'] }}
                            @endif
                            &middot; {{ $row['scope_label'] }}
                        </p>
                    </div>
                    <span class="rounded-full bg-oil-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-oil-700 dark:bg-oil-500/10 dark:text-oil-300">
                        {{ $row['branches']->count() }} Lokasi
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($row['can_act'])
                        <form method="POST" action="{{ route('context.store') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            <button
                                type="submit"
                                class="rounded-xl bg-gradient-to-r from-oil-500 to-oil-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-oil-600/20 transition hover:from-oil-600 hover:to-oil-700"
                            >
                                {{ $row['scope_label'] }}
                                @if ($isActive && ! $activeBranchId) &#10003; @endif
                            </button>
                        </form>
                    @endif

                    @foreach ($row['branches']->sortByDesc('is_head_office') as $branch)
                        <form method="POST" action="{{ route('context.store') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <button
                                type="submit"
                                class="flex flex-col items-start gap-0.5 rounded-xl border border-gray-200 bg-white px-3.5 py-1.5 text-left transition hover:border-oil-400 dark:border-white/10 dark:bg-white/5"
                            >
                                <span class="text-[9px] font-bold uppercase tracking-[0.16em] {{ $branch->is_head_office ? 'text-oil-600 dark:text-oil-300' : 'text-gray-400' }}">
                                    {{ $branch->is_head_office ? 'Kantor Pusat' : 'Cabang' }}
                                </span>
                                <span class="text-xs font-semibold text-petrol-900 dark:text-white">{{ $branch->name }}</span>
                            </button>
                        </form>
                    @endforeach

                    @if (! $row['can_act'] && $row['branches']->isEmpty())
                        <p class="text-xs text-gray-400">Tidak ada lokasi yang bisa dibuka untuk PT ini.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-white/60">
                You are not a member of any company yet. Contact your administrator.
            </div>
        @endforelse
    </div>
</x-guest-layout>
