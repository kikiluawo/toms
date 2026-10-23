<x-guest-layout title="Sign In">
    <span class="inline-flex items-center gap-2 rounded-full border border-oil-300/70 bg-oil-100/60 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-oil-700 dark:border-oil-500/30 dark:bg-oil-500/10 dark:text-oil-300">
        <span class="h-1.5 w-1.5 rounded-full bg-oil-500"></span>
        Secure Access
    </span>

    <h3 class="mt-5 text-3xl font-bold tracking-tight text-petrol-900 sm:text-4xl dark:text-white">
        Sign In
    </h3>
    <p class="mt-2 text-sm text-gray-600 dark:text-white/50">
        Access your oil operations dashboard.
    </p>

    @if (session('status'))
        <div class="mt-6 rounded-xl bg-green-50 p-3 text-sm font-medium text-green-600 dark:bg-green-900/30 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8">
        @csrf

        <div class="mb-4">
            <label for="username" class="ml-1.5 text-sm font-semibold text-petrol-900 dark:text-oil-100">Username*</label>
            <input
                id="username"
                type="text"
                name="username"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your username"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-300/80 bg-white p-3 text-sm text-navy-700 outline-none transition placeholder:text-gray-400 focus:border-oil-500 focus:ring-2 focus:ring-oil-500/20 dark:!border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('username')" />
        </div>

        <div class="mb-4">
            <label for="password" class="ml-1.5 text-sm font-semibold text-petrol-900 dark:text-oil-100">Password*</label>
            <div class="relative mt-2" x-data="{ show: false }">
                <input
                    id="password"
                    :type="show ? 'text' : 'password'"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="flex h-12 w-full items-center justify-center rounded-xl border border-gray-300/80 bg-white p-3 pr-12 text-sm text-navy-700 outline-none transition placeholder:text-gray-400 focus:border-oil-500 focus:ring-2 focus:ring-oil-500/20 dark:!border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-white/30"
                >
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-4 flex items-center text-gray-500 hover:text-petrol-900 dark:text-white/40 dark:hover:text-white">
                    <x-icon name="eye" class="h-5 w-5" x-show="!show" />
                    <x-icon name="eye-off" class="h-5 w-5" x-show="show" x-cloak />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mb-6 flex items-center px-1">
            <input
                id="remember"
                type="checkbox"
                name="remember"
                class="defaultCheckbox relative flex h-[20px] min-h-[20px] w-[20px] min-w-[20px] appearance-none items-center justify-center rounded-md border border-gray-300 text-white/0 outline-none transition duration-[0.2s] checked:border-none checked:bg-oil-500 checked:text-white hover:cursor-pointer dark:border-white/10 dark:checked:bg-oil-400"
            >
            <label for="remember" class="ml-2 text-sm font-medium text-petrol-900 dark:text-white">
                Keep me logged In
            </label>
        </div>

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-oil-500 to-oil-600 py-3 text-base font-semibold text-white shadow-lg shadow-oil-600/20 transition duration-200 hover:from-oil-600 hover:to-oil-700 active:from-oil-700 active:to-oil-800">
            Sign In
        </button>
    </form>

    <p class="mt-6 text-xs leading-relaxed text-gray-500 dark:text-white/30">
        Need access? Contact your operations administrator.
    </p>
</x-guest-layout>
