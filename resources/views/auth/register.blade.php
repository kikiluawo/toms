<x-guest-layout title="Sign Up">
    <h3 class="mb-2.5 text-4xl font-bold text-navy-700 dark:text-white">
        Sign Up
    </h3>
    <p class="mb-9 ml-1 text-base text-gray-600">
        Enter your details to create your account!
    </p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Name*</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Your full name"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <label for="username" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Username*</label>
            <input
                id="username"
                type="text"
                name="username"
                value="{{ old('username') }}"
                required
                autocomplete="username"
                placeholder="Choose a username"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('username')" />
        </div>

        <div class="mb-3">
            <label for="email" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Email*</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                placeholder="mail@simmmple.com"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <label for="password" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Password*</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Min. 8 characters"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Confirm Password*</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Repeat your password"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button type="submit" class="linear w-full rounded-xl bg-brand-500 py-3 text-base font-medium text-white transition duration-200 hover:bg-brand-600 active:bg-brand-700 dark:bg-brand-400 dark:text-white dark:hover:bg-brand-300 dark:active:bg-brand-200">
            Create Account
        </button>
    </form>

    <div class="mt-4">
        <span class="text-sm font-medium text-navy-700 dark:text-gray-500">
            Already registered?
        </span>
        <a href="{{ route('login') }}" class="ml-1 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-white">
            Sign In
        </a>
    </div>
</x-guest-layout>
