<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name, string $code): Company
    {
        return Company::create(['name' => $name, 'code' => $code]);
    }

    private function grant(User $user, string $menu): void
    {
        $user->menuPermissions()->create(['menu_key' => $menu]);
    }

    public function test_structure_screen_lists_every_active_company_for_a_group_user(): void
    {
        $ceo = User::factory()->create();
        $anchor = $this->company('PT. Turbion Energy International', 'TEI');
        $other = $this->company('PT. Majesty Prosperindo', 'MP');
        $other->departments()->create(['name' => 'MARKETING']);
        $other->positions()->create(['name' => 'Marketing', 'department_id' => $other->departments()->value('id')]);

        $ceo->companies()->attach($anchor->id, ['role' => 'ceo', 'data_scope' => 'group']);
        $this->grant($ceo, 'structure');

        $this->actingAs($ceo)
            ->get(route('structure.index'))
            ->assertOk()
            ->assertSee('Kamus bersama')
            ->assertSee($anchor->name)
            ->assertSee($other->name)
            ->assertSee('MARKETING')
            ->assertSee('Marketing');
    }

    public function test_structure_requires_the_structure_menu(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $user->companies()->attach($company, ['role' => 'manager', 'data_scope' => 'company']);

        $this->actingAs($user)
            ->get(route('structure.index'))
            ->assertForbidden();
    }

    public function test_a_restricted_user_only_sees_their_own_company(): void
    {
        $user = User::factory()->create();
        $home = $this->company('PT. Majesty Prosperindo', 'MP');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');

        $user->companies()->attach($home, ['role' => 'staf', 'data_scope' => 'branch']);
        $this->grant($user, 'structure');

        $this->actingAs($user)
            ->get(route('structure.index'))
            ->assertOk()
            ->assertSee($home->name)
            ->assertDontSee($foreign->name)
            ->assertDontSee('Kamus bersama');
    }

    public function test_group_user_can_add_a_dictionary_entry_to_a_company_they_are_not_a_member_of(): void
    {
        $ceo = User::factory()->create();
        $anchor = $this->company('PT. Turbion Energy International', 'TEI');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');

        $ceo->companies()->attach($anchor->id, ['role' => 'coo', 'data_scope' => 'group']);
        $this->grant($ceo, 'structure');

        $this->actingAs($ceo)
            ->post(route('structure.departments.store'), [
                'company_id' => $foreign->id,
                'name' => 'logistik',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('departments', [
            'company_id' => $foreign->id,
            'name' => 'LOGISTIK',
        ]);
    }

    public function test_company_manager_can_extend_their_own_dictionary(): void
    {
        $manager = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $manager->companies()->attach($company, ['role' => 'manager', 'data_scope' => 'company']);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->post(route('structure.positions.store'), [
                'company_id' => $company->id,
                'name' => 'Admin Penjualan',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('positions', [
            'company_id' => $company->id,
            'name' => 'Admin Penjualan',
            'department_id' => null,
        ]);
    }

    public function test_company_manager_cannot_touch_another_company(): void
    {
        $manager = User::factory()->create();
        $home = $this->company('PT. Majesty Prosperindo', 'MP');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');

        $manager->companies()->attach($home, ['role' => 'manager', 'data_scope' => 'company']);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->post(route('structure.departments.store'), [
                'company_id' => $foreign->id,
                'name' => 'DILARANG',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['name' => 'DILARANG']);
    }

    public function test_branch_user_cannot_add_a_department_at_company_level(): void
    {
        $staff = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $staff->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);
        $this->grant($staff, 'structure');

        $this->actingAs($staff)
            ->post(route('structure.departments.store'), [
                'company_id' => $company->id,
                'name' => 'KEUANGAN',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_shared_dictionary_requires_group_scope(): void
    {
        $manager = User::factory()->create();
        $home = $this->company('PT. Majesty Prosperindo', 'MP');
        $manager->companies()->attach($home, ['role' => 'manager', 'data_scope' => 'company']);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->post(route('structure.departments.store'), [
                'company_id' => null,
                'name' => 'EKSEKUTIF',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_a_group_user_can_fill_the_shared_dictionary(): void
    {
        $ceo = User::factory()->create();
        $company = $this->company('PT. Turbion Energy International', 'TEI');
        $ceo->companies()->attach($company->id, ['role' => 'ceo', 'data_scope' => 'group']);
        $this->grant($ceo, 'structure');

        $this->actingAs($ceo)
            ->post(route('structure.departments.store'), [
                'company_id' => null,
                'name' => 'Corporate',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['company_id' => null, 'name' => 'CORPORATE']);
    }

    public function test_a_name_only_has_to_be_unique_within_its_company(): void
    {
        $ceo = User::factory()->create();
        $first = $this->company('PT. Majesty Prosperindo', 'MP');
        $second = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');
        $first->departments()->create(['name' => 'MARKETING']);
        $ceo->companies()->attach($first->id, ['role' => 'ceo', 'data_scope' => 'group']);
        $this->grant($ceo, 'structure');

        $this->actingAs($ceo)
            ->post(route('structure.departments.store'), ['company_id' => $first->id, 'name' => 'marketing'])
            ->assertSessionHasErrors('department_'.$first->id.'_name');

        $this->actingAs($ceo)
            ->post(route('structure.departments.store'), ['company_id' => $second->id, 'name' => 'MARKETING'])
            ->assertRedirect();

        $this->assertDatabaseCount('departments', 2);
    }

    public function test_a_position_cannot_reference_a_department_of_another_company(): void
    {
        $manager = User::factory()->create();
        $home = $this->company('PT. Majesty Prosperindo', 'MP');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');

        $shared = Department::create(['name' => 'EKSEKUTIF']);
        $foreignDepartment = $foreign->departments()->create(['name' => 'OPERASIONAL']);

        $manager->companies()->attach($home, ['role' => 'manager', 'data_scope' => 'company']);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->post(route('structure.positions.store'), [
                'company_id' => $home->id,
                'name' => 'Pengawas',
                'department_id' => $foreignDepartment->id,
            ])
            ->assertSessionHasErrors('position_'.$home->id.'_department_id');

        $this->actingAs($manager)
            ->post(route('structure.positions.store'), [
                'company_id' => $home->id,
                'name' => 'Pengawas',
                'department_id' => $shared->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('positions', [
            'company_id' => $home->id,
            'name' => 'Pengawas',
            'department_id' => $shared->id,
        ]);
    }

    public function test_a_department_still_used_by_a_user_cannot_be_deleted(): void
    {
        $manager = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $department = $company->departments()->create(['name' => 'MARKETING']);
        $position = $company->positions()->create(['name' => 'Marketing', 'department_id' => $department->id]);

        $manager->companies()->attach($company, [
            'role' => 'manager',
            'data_scope' => 'company',
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->delete(route('structure.departments.destroy', $department))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);

        $this->actingAs($manager)
            ->delete(route('structure.positions.destroy', $position))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_an_unused_entry_can_be_deleted(): void
    {
        $manager = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $department = $company->departments()->create(['name' => 'MARKETING']);
        $position = $company->positions()->create(['name' => 'SPV Marketing', 'department_id' => $department->id]);

        $manager->companies()->attach($company, ['role' => 'manager', 'data_scope' => 'company']);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->delete(route('structure.positions.destroy', $position))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('positions', 0);

        $this->actingAs($manager)
            ->delete(route('structure.departments.destroy', $department))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('departments', 0);
    }

    public function test_deleting_another_companys_entry_is_forbidden(): void
    {
        $manager = User::factory()->create();
        $home = $this->company('PT. Majesty Prosperindo', 'MP');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');
        $foreignDepartment = $foreign->departments()->create(['name' => 'OPERASIONAL']);
        $foreignPosition = $foreign->positions()->create(['name' => 'Pengawas Terminal']);

        $manager->companies()->attach($home, ['role' => 'manager', 'data_scope' => 'company']);
        $this->grant($manager, 'structure');

        $this->actingAs($manager)
            ->delete(route('structure.departments.destroy', $foreignDepartment))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('structure.positions.destroy', $foreignPosition))
            ->assertForbidden();

        $this->assertEquals(1, Department::count());
        $this->assertEquals(1, Position::count());
    }
}
