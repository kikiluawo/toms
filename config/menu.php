<?php

return [
    'items' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'icon' => 'home',
            'route' => 'dashboard',
            'pattern' => 'dashboard',
            'always' => true,
        ],
        'companies' => [
            'label' => 'Master PT',
            'icon' => 'building',
            'route' => 'companies.index',
            'pattern' => 'companies.*',
            'description' => 'Buat, nonaktifkan, dan hapus PT beserta kantor pusat dan cabangnya.',
            'system_only' => true,
        ],
        'users' => [
            'label' => 'Users',
            'icon' => 'users',
            'route' => 'users.index',
            'pattern' => 'users.*',
            'description' => 'Tambah user, atur PT dan lokasi, dan kelola hak akses menu.',
        ],
        'structure' => [
            'label' => 'Struktur',
            'icon' => 'building',
            'route' => 'structure.index',
            'pattern' => 'structure.*',
            'description' => 'Kamus department dan posisi per PT.',
        ],
        'catalog' => [
            'label' => 'Katalog Produk',
            'icon' => 'document',
            'route' => 'catalog.index',
            'pattern' => 'catalog.*',
            'description' => 'Daftar produk minyak — satu katalog untuk semua PT.',
        ],
        'suppliers' => [
            'label' => 'Pemasok',
            'icon' => 'building',
            'route' => 'suppliers.index',
            'pattern' => 'suppliers.*',
            'description' => 'CRUD pemasok / vendor yang dipakai bersama lintas PT.',
        ],
        'orders' => [
            'label' => 'Order Minyak',
            'icon' => 'cart',
            'route' => 'orders.index',
            'pattern' => 'orders.*',
            'description' => 'Buat dan ajukan purchase order, lalu catat realisasi/penerimaannya.',
        ],
        'stock' => [
            'label' => 'Stok',
            'icon' => 'wallet',
            'route' => 'stock.index',
            'pattern' => 'stock.*',
            'description' => 'Saldo minyak per lokasi dan riwayat mutasinya.',
        ],
        'profile' => [
            'label' => 'Profile',
            'icon' => 'user',
            'route' => 'profile.edit',
            'pattern' => 'profile.*',
            'always' => true,
        ],
    ],
];
