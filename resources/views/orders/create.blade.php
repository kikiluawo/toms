<x-app-layout title="Order Baru">
    <div class="mt-3 space-y-5">
        <div class="horizon-card p-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400">Pengadaan</p>
            <h2 class="mt-1 text-xl font-bold text-navy-700 dark:text-white">Buat order minyak</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-white/50">
                Isi pemasok, lokasi pembongkaran, dan daftar produknya. Simpan sebagai draft lebih dulu,
                baru diajukan saat datanya sudah pasti.
            </p>
        </div>

        @include('orders.partials.form', ['order' => null, 'action' => route('orders.store'), 'method' => 'POST'])
    </div>
</x-app-layout>
