<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center justify-center rounded-lg bg-lightPrimary p-2 text-brand-500 transition duration-200 hover:bg-gray-100 hover:cursor-pointer dark:bg-navy-700 dark:text-white dark:hover:bg-white/20 dark:active:bg-white/10"
    >
        <x-icon name="dots" class="h-6 w-6" />
    </button>

    <div
        x-cloak
        x-show="open"
        @click.outside="open = false"
        x-transition
        class="absolute right-0 top-11 z-50 w-max rounded-xl bg-white px-4 py-3 text-sm shadow-xl shadow-shadow-500 dark:!bg-navy-700 dark:shadow-none"
    >
        <p class="flex cursor-pointer items-center gap-2 text-gray-600 hover:font-medium hover:text-black">
            <span><x-icon name="dashboard" class="h-4 w-4" /></span>
            Panel 1
        </p>
        <p class="mt-2 flex cursor-pointer items-center gap-2 pt-1 text-gray-600 hover:font-medium hover:text-black">
            <span><x-icon name="cart" class="h-4 w-4" /></span>
            Panel 2
        </p>
        <p class="mt-2 flex cursor-pointer items-center gap-2 pt-1 text-gray-600 hover:font-medium hover:text-black">
            <span><x-icon name="chart" class="h-4 w-4" /></span>
            Panel 3
        </p>
        <p class="mt-2 flex cursor-pointer items-center gap-2 pt-1 text-gray-600 hover:font-medium hover:text-black">
            <span><x-icon name="settings" class="h-4 w-4" /></span>
            Panel 4
        </p>
    </div>
</div>
