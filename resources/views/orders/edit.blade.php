<x-app-layout title="Edit Order">
    <div class="mt-3 space-y-5">
        <div class="horizon-card flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Draft</p>
                <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">{{ $order->po_number }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                    {{ $order->company->name }} • {{ $order->branch?->name ?? 'seluruh lokasi' }} •
                    order {{ $order->order_date->format('d M Y') }}
                </p>
            </div>
            <a href="{{ route('orders.show', $order) }}" class="flex items-center gap-2 self-start rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-500 transition hover:bg-lightPrimary dark:border-white/10 dark:text-white/50 dark:hover:bg-white/5">
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali
            </a>
        </div>

        @include('orders.partials.form', ['action' => route('orders.update', $order), 'method' => 'PUT'])
    </div>
</x-app-layout>
