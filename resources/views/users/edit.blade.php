<x-app-layout title="Edit User">
    <div class="mt-3 space-y-6">
        <div class="flex flex-col gap-4 border-b border-gray-200/70 pb-6 dark:border-white/10 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400">
                    <a href="{{ route('users.index') }}" class="transition hover:text-brand-500">Users</a>
                    <x-icon name="chevron-right" class="h-3 w-3" />
                    <span class="text-oil-600 dark:text-oil-300">Edit</span>
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-navy-700 dark:text-white md:text-3xl">Edit {{ $user->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-white/50">Perbarui data akun, posisi, akses PT/cabang, dan hak akses menu.</p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-[11px] font-semibold text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-white/60">&#64;{{ $user->username }}</span>
                    <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-[11px] font-semibold text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-white/60">{{ $user->email }}</span>
                    <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-[11px] font-semibold text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-white/60">{{ $user->companies->count() }} PT</span>
                </div>
            </div>
            <a href="{{ route('users.index') }}" class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-navy-700 shadow-sm transition hover:bg-lightPrimary dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali
            </a>
        </div>

        @include('users.partials.form', [
            'action' => route('users.update', $user),
            'method' => 'PATCH',
            'submitLabel' => 'Simpan perubahan',
        ])
    </div>
</x-app-layout>
