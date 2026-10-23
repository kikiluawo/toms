<x-guest-layout title="Forgot Password">
    <h3 class="mb-2.5 text-4xl font-bold text-navy-700 dark:text-white">
        Forgot Password?
    </h3>
    <p class="mb-9 ml-1 text-base text-gray-600">
        Enter your email address and we will email you a password reset link.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-green-50 p-3 text-sm font-medium text-green-600 dark:bg-green-900/30 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="ml-1.5 text-sm font-medium text-navy-700 dark:text-white">Email*</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="mail@simmmple.com"
                class="mt-2 flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white/0 p-3 text-sm outline-none transition focus:border-brand-500 focus:ring-0 dark:!border-white/10 dark:text-white dark:placeholder:text-white/30"
            >
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <button type="submit" class="linear w-full rounded-xl bg-brand-500 py-3 text-base font-medium text-white transition duration-200 hover:bg-brand-600 active:bg-brand-700 dark:bg-brand-400 dark:text-white dark:hover:bg-brand-300 dark:active:bg-brand-200">
            Email Password Reset Link
        </button>
    </form>

    <div class="mt-4">
        <a href="{{ route('login') }}" class="text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-white">
            Back to Sign In
        </a>
    </div>
</x-guest-layout>
