<x-guest-layout title="Verify Email">
    <h3 class="mb-2.5 text-4xl font-bold text-navy-700 dark:text-white">
        Verify Email
    </h3>
    <p class="mb-9 ml-1 text-base text-gray-600">
        Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl bg-green-50 p-3 text-sm font-medium text-green-600 dark:bg-green-900/30 dark:text-green-400">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="linear rounded-xl bg-brand-500 px-5 py-3 text-sm font-medium text-white transition duration-200 hover:bg-brand-600 active:bg-brand-700 dark:bg-brand-400 dark:hover:bg-brand-300 dark:active:bg-brand-200">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-white">
                Log Out
            </button>
        </form>
    </div>
</x-guest-layout>
