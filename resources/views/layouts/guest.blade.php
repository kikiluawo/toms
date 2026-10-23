<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        <script>
            (function () {
                const storedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#F7F4EC] font-sans text-navy-700 antialiased dark:bg-petrol-950 dark:text-white">
        <div class="flex min-h-screen">
            <div class="relative flex min-h-screen w-full flex-col px-6 pb-6 pt-8 sm:px-10 lg:w-1/2 lg:px-14 xl:px-20">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-oil-200/50 to-transparent dark:from-oil-500/10"></div>

                <div class="relative flex items-center gap-3">
                    <x-logo />
                    <div>
                        <p class="text-lg font-bold leading-none tracking-tight text-petrol-900 dark:text-white">
                            TURBION
                        </p>
                        <p class="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-white/40">
                            OIL Management System
                        </p>
                    </div>
                </div>

                <div class="relative flex flex-1 items-center justify-center py-12">
                    <div class="w-full max-w-[420px]">
                        {{ $slot }}
                    </div>
                </div>

                @include('layouts.partials.footer-auth')
            </div>

            <div class="relative hidden overflow-hidden lg:block lg:w-1/2">
                <div class="absolute inset-0 bg-gradient-to-br from-[#04100F] via-petrol-900 to-petrol-800"></div>
                <div class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-oil-500/25 blur-3xl"></div>
                <div class="absolute -right-20 bottom-20 h-80 w-80 rounded-full bg-horizonTeal-500/20 blur-3xl"></div>
                <div
                    class="absolute inset-0 opacity-[0.12]"
                    style="background-image: linear-gradient(rgba(255,255,255,.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.5) 1px, transparent 1px); background-size: 64px 64px;"
                ></div>

                <x-oil-pumpjack class="pointer-events-none absolute left-1/2 top-[46%] w-[126%] max-w-none -translate-x-1/2 -translate-y-1/2" />

                <div class="relative z-10 flex h-full flex-col justify-between p-10 xl:p-14">
                    <div class="flex items-start justify-between gap-4">
                        <div class="max-w-[210px] rounded-2xl border border-white/10 bg-white/[0.06] px-5 py-4 backdrop-blur-md">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-oil-500/15 text-oil-300">
                                <x-icon name="chart" class="h-5 w-5" />
                            </div>
                            <p class="mt-3 text-sm font-semibold text-white">Operations overview</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-white/45">
                                Production, storage and distribution in one dashboard.
                            </p>
                        </div>

                        <div class="mt-20 max-w-[190px] rounded-2xl border border-white/10 bg-white/[0.06] px-5 py-4 backdrop-blur-md">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-oil-500/15 text-oil-300">
                                <x-icon name="lock" class="h-5 w-5" />
                            </div>
                            <p class="mt-3 text-sm font-semibold text-white">Secure access</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-white/45">
                                Role-based access for your operations team.
                            </p>
                        </div>
                    </div>

                    <div class="max-w-md">
                        <span class="inline-flex items-center gap-2 rounded-full border border-oil-400/30 bg-oil-400/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-oil-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-oil-300"></span>
                            Petroleum operations
                        </span>
                        <h1 class="mt-4 text-3xl font-bold leading-tight text-white 2xl:text-4xl">
                            Every barrel,<br />
                            <span class="bg-gradient-to-r from-oil-200 via-oil-300 to-oil-500 bg-clip-text text-transparent">under control.</span>
                        </h1>
                        <p class="mt-3 max-w-sm text-sm leading-relaxed text-white/50">
                            Monitor production, storage and distribution across your oil operations from a single dashboard.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
