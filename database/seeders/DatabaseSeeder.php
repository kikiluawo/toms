<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
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
        $this->call(OilProductSeeder::class);

        // System account: no PT membership at all, but it can create PTs, dictionaries and users.
        User::factory()->create([
            'name' => 'Sistem Turbion',
            'username' => 'superadmin',
            'email' => 'superadmin@oilms.test',
            'password' => 'password',
            'is_superadmin' => true,
        ]);

        $companies = [
            'MP' => [
                'name' => 'PT. Majesty Prosperindo',
                'branches' => [
                    ['name' => 'Prosperindo Batam', 'code' => 'MP-BTM', 'is_head_office' => true],
                    ['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT'],
                    ['name' => 'Prosperindo Karimun', 'code' => 'MP-KRM'],
                ],
                'departments' => [
                    'MARKETING' => ['Marketing'],
                    'ACCOUNTING' => ['SPV Accounting'],
                ],
            ],
            'NJKM' => [
                'name' => 'PT. Nusa Jaya Karya Mandiri',
                'branches' => [
                    ['name' => 'NJKM Batam', 'code' => 'NJKM-BTM', 'is_head_office' => true],
                    ['name' => 'NJKM Bintan (Lobam)', 'code' => 'NJKM-BTN'],
                    ['name' => 'NJKM Kijang', 'code' => 'NJKM-KJG'],
                ],
                'departments' => [
                    'OPERASIONAL' => ['Pengawas Terminal'],
                ],
            ],
            'TEI' => [
                'name' => 'PT. Turbion Energy International',
                'branches' => [],
                'departments' => [
                    'MARKETING' => ['Marketing'],
                ],
            ],
        ];

        $created = [];

        foreach ($companies as $code => $data) {
            $company = Company::create([
                'name' => $data['name'],
                'code' => $code,
            ]);

            foreach ($data['branches'] as $branch) {
                $company->branches()->create($branch);
            }

            foreach ($data['departments'] as $departmentName => $positions) {
                $department = $company->departments()->create(['name' => $departmentName]);

                foreach ($positions as $positionName) {
                    $company->positions()->create([
                        'name' => $positionName,
                        'department_id' => $department->id,
                    ]);
                }
            }

            $created[$code] = $company;
        }

        // Group-wide dictionary, usable at every company.
        $executive = Department::create(['name' => 'EKSEKUTIF']);

        foreach (['CEO', 'CFO', 'COO'] as $positionName) {
            Position::create(['name' => $positionName, 'department_id' => $executive->id]);
        }

        $director = User::factory()->create([
            'name' => 'Direktur Turbion',
            'username' => 'ceo',
            'email' => 'ceo@oilms.test',
            'password' => 'password',
        ]);

        // One anchor membership is enough: role ceo carries data_scope = group.
        $director->companies()->attach($created['TEI']->id, [
            'role' => 'ceo',
            'data_scope' => 'group',
            'department_id' => $executive->id,
            'position_id' => Position::where('name', 'CEO')->value('id'),
        ]);

        $director->menuPermissions()->createMany(
            collect(config('menu.items'))
                ->reject(fn ($item) => ($item['always'] ?? false) || ($item['system_only'] ?? false))
                ->keys()
                ->map(fn ($key) => ['menu_key' => $key])
                ->all()
        );

        $manager = User::factory()->create([
            'name' => 'Manager Majesty',
            'username' => 'manager',
            'email' => 'manager@oilms.test',
            'password' => 'password',
        ]);

        $manager->companies()->attach($created['MP']->id, [
            'role' => 'manager',
            'data_scope' => 'company',
            'department_id' => $created['MP']->departments()->where('name', 'ACCOUNTING')->value('id'),
            'position_id' => $created['MP']->positions()->where('name', 'SPV Accounting')->value('id'),
        ]);

        $manager->menuPermissions()->createMany([
            ['menu_key' => 'users'],
            ['menu_key' => 'structure'],
        ]);

        $operator = User::factory()->create([
            'name' => 'Operator Jakarta',
            'username' => 'operator',
            'email' => 'operator@oilms.test',
            'password' => 'password',
        ]);

        $operator->companies()->attach($created['MP']->id, [
            'role' => 'staf',
            'data_scope' => 'branch',
            'department_id' => $created['MP']->departments()->where('name', 'MARKETING')->value('id'),
            'position_id' => $created['MP']->positions()->where('name', 'Marketing')->value('id'),
        ]);

        $operator->branches()->attach(
            $created['MP']->branches()->where('code', 'MP-JKT')->value('id')
        );
    }
}
