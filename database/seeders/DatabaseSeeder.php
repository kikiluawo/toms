<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@oilms.test',
            'password' => 'password',
        ]);

        $companies = [
            [
                'name' => 'PT. Majesty Prosperindo',
                'code' => 'MP',
                'branches' => [
                    ['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT'],
                    ['name' => 'Prosperindo Karimun', 'code' => 'MP-KRM'],
                ],
            ],
            [
                'name' => 'PT. Nusa Jaya Karya Mandiri',
                'code' => 'NJKM',
                'branches' => [
                    ['name' => 'NJKM Jakarta (Kijang)', 'code' => 'NJKM-JKT'],
                    ['name' => 'NJKM Bintan (Lobam)', 'code' => 'NJKM-BTN'],
                ],
            ],
            [
                'name' => 'PT. Turbion Energy International',
                'code' => 'TEI',
                'branches' => [],
            ],
        ];

        foreach ($companies as $data) {
            $company = Company::create([
                'name' => $data['name'],
                'code' => $data['code'],
            ]);

            foreach ($data['branches'] as $branch) {
                $company->branches()->create($branch);
            }

            $admin->companies()->attach($company->id, [
                'role' => 'admin',
                'all_branches' => true,
            ]);
        }
    }
}
