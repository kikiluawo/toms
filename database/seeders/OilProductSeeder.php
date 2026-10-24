<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Produk dipakai bersama oleh semua PT, jadi katalognya satu daftar global dan tidak
 * pernah ikut di-seed per perusahaan. Aman dijalankan ulang: kode yang sudah ada
 * dibiarkan apa adanya, termasuk kalau isinya sudah kamu koreksi sendiri.
 */
class OilProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::products() as $product) {
            Product::query()->firstOrCreate(['code' => $product['code']], $product);
        }
    }

    /** @return array<int, array<string, string>> */
    public static function products(): array
    {
        return [
            ['code' => 'TDO', 'name' => 'Turbion Diesel Oil (TDO)', 'category' => 'solar', 'unit' => 'liter'],
            ['code' => 'MGO-B40', 'name' => 'MGO B40', 'category' => 'solar', 'unit' => 'liter'],
            ['code' => 'HSFO', 'name' => 'HSFO', 'category' => 'fuel_oil', 'unit' => 'liter'],
            ['code' => 'LSFO', 'name' => 'LSFO', 'category' => 'fuel_oil', 'unit' => 'liter'],
            ['code' => 'BIT-6070', 'name' => 'Bitumen Grade 60/70', 'category' => 'bitumen', 'unit' => 'kg'],
            ['code' => 'BIT-6070-DRUM', 'name' => 'Drummed Bitumen Grade 60/70 190Kg (Apprx)', 'category' => 'bitumen', 'unit' => 'drum'],
        ];
    }
}
