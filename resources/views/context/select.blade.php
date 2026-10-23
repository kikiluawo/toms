<x-guest-layout title="Select Company">
    <h3 class="text-3xl font-bold tracking-tight text-petrol-900 sm:text-4xl dark:text-white">
        Select Company
    </h3>
    <p class="mt-2 text-sm text-gray-600 dark:text-white/50">
        Choose the company and branch you want to manage. You can switch anytime.
    </p>

    <div class="mt-8 space-y-4">
        @forelse ($memberships as $company)
            @php
                $branches = $company->branches;

                if (! $company->pivot->all_branches) {
                    $branches = $branches->whereIn('id', $allowedBranchIds);
                }
            @endphp

            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-petrol-900 dark:text-white">{{ $company->name }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">
                            {{ $company->code }} &middot; {{ $company->pivot->role }}
                        </p>
                    </div>
                    <span class="rounded-full bg-oil-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-oil-700 dark:bg-oil-500/10 dark:text-oil-300">
                        {{ $branches->count() }} {{ \Illuminate\Support\Str::plural('branch', $branches->count()) }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('context.store') }}">
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $company->id }}">
                        <button
                            type="submit"
                            class="rounded-xl bg-gradient-to-r from-oil-500 to-oil-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-oil-600/20 transition hover:from-oil-600 hover:to-oil-700"
                        >
                            {{ $branches->isEmpty() ? 'Open company' : ($company->pivot->all_branches ? 'All branches' : 'All assigned branches') }}
                        </button>
                    </form>

                    @foreach ($branches as $branch)
                        <form method="POST" action="{{ route('context.store') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <button
                                type="submit"
                                class="rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-petrol-900 transition hover:border-oil-400 hover:text-oil-700 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-oil-400/40 dark:hover:text-oil-300"
                            >
                                {{ $branch->name }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-white/60">
                You are not a member of any company yet. Contact your administrator.
            </div>
        @endforelse
    </div>
</x-guest-layout>
