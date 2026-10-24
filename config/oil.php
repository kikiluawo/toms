<?php

return [
    // Kategori barang. Dipakai untuk mengelompokkan daftar produk dan laporan nanti.
    'categories' => [
        'solar' => 'Solar / Diesel',
        'bensin' => 'Bensin',
        'fuel_oil' => 'Fuel Oil',
        'bitumen' => 'Bitumen',
        'pelumas' => 'Pelumas',
        'grease' => 'Grease',
        'lainnya' => 'Lainnya',
    ],

    // Satuan dasar tiap produk. Semua pencatatan stok memakai satuan ini, jadi satu
    // produk tidak pernah punya dua satuan berbeda di buku stok.
    'units' => [
        'liter' => 'Liter',
        'kg' => 'Kilogram',
        'drum' => 'Drum',
        'pail' => 'Pail',
        'pcs' => 'Pcs',
    ],

    'po_status' => [
        'draft' => 'Draft',
        'submitted' => 'Diajukan',
        'completed' => 'Selesai',
    ],

    // Role yang boleh memesan minyak. Sengaja di sini, bukan hardcode di controller,
    // supaya nanti bisa ditambah tanpa membongkar logika.
    'order_roles' => ['coo'],
];
