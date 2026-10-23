<x-guest-layout title="Confirm Password">
    <h3 class="mb-2.5 text-4xl font-bold text-navy-700 dark:text-white">
        Confirm Password
    </h3>
    <p class="mb-9 ml-1 text-base text-gray-600">
        This is a secure area of the application. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4">
            <label for="password" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Password*</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Your current password"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <button type="submit" class="linear w-full rounded-xl bg-brand-500 py-3 text-base font-medium text-white transition duration-200 hover:bg-brand-600 active:bg-brand-700 dark:bg-brand-400 dark:text-white dark:hover:bg-brand-300 dark:active:bg-brand-200">
            Confirm
        </button>
    </form>
</x-guest-layout>
