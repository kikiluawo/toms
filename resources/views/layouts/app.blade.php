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
    <body class="font-dm antialiased">
        <div x-data="{ sidebarOpen: false }" class="flex h-full w-full bg-background-100 dark:bg-background-900">
            @include('layouts.partials.sidebar')

            <div class="h-full w-full font-dm dark:bg-navy-900">
                <main class="mx-2.5 flex-none transition-all dark:bg-navy-900 md:pr-2 xl:ml-[323px]">
                    <div>
                        @include('layouts.partials.navbar')

                        @isset($header)
                            <div class="px-2 py-3 md:px-2">
                                {{ $header }}
                            </div>
                        @endisset

                        <div class="mx-auto min-h-screen p-2 !pt-[10px] md:p-2">
                            {{ $slot }}
                        </div>

                        <div class="p-3">
                            @include('layouts.partials.footer')
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
