<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['is_superadmin' => true]);
    }

    private function company(string $name, string $code): Company
    {
        return Company::create(['name' => $name, 'code' => $code]);
    }

    public function test_a_system_account_needs_no_membership_to_reach_every_active_company(): void
    {
        $admin = $this->superadmin();
        $first = $this->company('PT. Majesty Prosperindo', 'MP');
        $second = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');
        $this->company('PT. Mati Total', 'MTI')->update(['is_active' => false]);

        $context = \App\Support\UserContext::for($admin);

        $this->assertSame('system', $context->dataScope());
        $this->assertTrue($context->isGroup());
        $this->assertTrue($context->canActOn($first->id));
        $this->assertTrue($context->canActOn($second->id));
        $this->assertSame([$first->id, $second->id], $context->visibleCompanyIds());
    }

    public function test_the_system_account_reaches_the_dashboard_without_picking_a_company(): void
    {
        $admin = $this->superadmin();
        $this->company('PT. Majesty Prosperindo', 'MP');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_the_system_account_sees_menus_it_was_never_granted(): void
    {
        $admin = $this->superadmin();

        $this->assertTrue($admin->hasMenuAccess('users'));
        $this->assertTrue($admin->hasMenuAccess('structure'));
        $this->assertTrue($admin->hasMenuAccess('companies'));

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('structure.index'))->assertOk();
        $this->actingAs($admin)->get(route('companies.index'))->assertOk();
    }

    public function test_only_a_system_account_can_open_the_master_pt_screen(): void
    {
        $ceo = User::factory()->create();
        $ceo->companies()->attach($this->company('PT. Turbion Energy International', 'TEI')->id, [
            'role' => 'ceo',
            'data_scope' => 'group',
        ]);
        $ceo->menuPermissions()->create(['menu_key' => 'companies']);

        $this->assertFalse($ceo->hasMenuAccess('companies'));
        $this->actingAs($ceo)->get(route('companies.index'))->assertForbidden();
        $this->actingAs($ceo)->post(route('companies.store'), [
            'name' => 'PT. Ilegal',
            'code' => 'ILG',
        ])->assertForbidden();

        $this->assertDatabaseMissing('companies', ['code' => 'ILG']);
    }

    public function test_the_system_account_can_create_a_company_and_its_branches(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post(route('companies.store'), [
            'name' => 'PT. Turbion Energy International',
            'code' => 'tei',
        ])->assertSessionHas('status');

        $company = Company::where('code', 'TEI')->firstOrFail();

        $this->actingAs($admin)->post(route('companies.branches.store', $company), [
            'name' => 'Turbion Jakarta',
            'code' => 'tei-jkt',
            'is_head_office' => '1',
        ])->assertSessionHas('status');

        $this->assertTrue($company->branches()->first()->is_head_office);
        $this->assertSame('TEI-JKT', $company->branches()->first()->code);
    }

    public function test_two_branches_of_one_company_cannot_share_a_code(): void
    {
        $admin = $this->superadmin();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);

        $this->actingAs($admin)->post(route('companies.branches.store', $company), [
            'name' => 'Duplikat',
            'code' => 'mp-jkt',
        ])->assertSessionHas('error');

        $this->assertSame(1, $company->branches()->count());
    }

    public function test_a_company_can_be_deactivated_and_switched_back_on(): void
    {
        $admin = $this->superadmin();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');

        $this->actingAs($admin)->patch(route('companies.update', $company), [
            'name' => 'PT. Majesty Prosperindo',
            'code' => 'MP',
            'is_active' => '0',
        ])->assertSessionHas('status');

        $this->assertFalse($company->fresh()->is_active);

        $context = \App\Support\UserContext::for($admin);
        $this->assertNotContains($company->id, $context->visibleCompanyIds());

        $this->actingAs($admin)->patch(route('companies.update', $company), [
            'name' => 'PT. Majesty Prosperindo',
            'code' => 'MP',
            'is_active' => '1',
        ]);

        $this->assertTrue($company->fresh()->is_active);
    }

    public function test_a_company_with_members_is_deactivated_not_deleted(): void
    {
        $admin = $this->superadmin();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $company->users()->attach(User::factory()->create()->id, ['role' => 'staf', 'data_scope' => 'branch']);

        $this->actingAs($admin)->delete(route('companies.destroy', $company))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_deleting_an_empty_company_takes_its_branches_and_dictionary_with_it(): void
    {
        $admin = $this->superadmin();
        $company = $this->company('PT. Turbion Energy International', 'TEI');
        $branch = $company->branches()->create(['name' => 'Turbion Karimun', 'code' => 'TEI-KRM']);
        $department = $company->departments()->create(['name' => 'OPERASIONAL']);
        $company->positions()->create(['name' => 'Pengawas', 'department_id' => $department->id]);

        $this->actingAs($admin)->delete(route('companies.destroy', $company))->assertSessionHas('status');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        $this->assertDatabaseMissing('positions', ['company_id' => $company->id]);
    }

    public function test_the_system_account_manages_companies_without_becoming_theirs(): void
    {
        $admin = $this->superadmin();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');

        $this->assertSame(0, $admin->companies()->count());

        $this->actingAs($admin)->get(route('structure.index'))->assertOk();
        $this->assertSame(0, $admin->fresh()->companies()->count());
    }

    public function test_a_group_director_cannot_touch_the_system_account(): void
    {
        $admin = $this->superadmin();
        $ceo = User::factory()->create();
        $company = $this->company('PT. Turbion Energy International', 'TEI');
        $ceo->companies()->attach($company->id, ['role' => 'ceo', 'data_scope' => 'group']);
        $ceo->menuPermissions()->create(['menu_key' => 'users']);

        $this->assertFalse(\App\Support\UserContext::for($ceo)->canManage($admin));

        $this->actingAs($ceo)->get(route('users.edit', $admin))->assertForbidden();
        $this->actingAs($ceo)->delete(route('users.destroy', $admin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_the_user_form_cannot_promote_anyone_to_the_system_level(): void
    {
        $admin = $this->superadmin();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $branch = $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mantan Harapan',
            'username' => 'hacker',
            'email' => 'hacker@oilms.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_superadmin' => '1',
            'companies' => [$company->id => ['selected' => '1', 'role' => 'staf']],
            'branches' => [$branch->id],
            'menus' => ['users'],
        ])->assertSessionHasNoErrors();

        $created = User::where('username', 'hacker')->firstOrFail();
        $this->assertFalse((bool) $created->is_superadmin);
    }

    public function test_a_system_account_lists_users_that_have_no_pt_yet(): void
    {
        $admin = $this->superadmin();
        $orphan = User::factory()->create(['name' => 'Calon User']);
        $this->company('PT. Majesty Prosperindo', 'MP');

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Calon User');

        $manager = User::factory()->create();
        $manager->companies()->attach(Company::first()->id, ['role' => 'manager', 'data_scope' => 'company']);
        $manager->menuPermissions()->create(['menu_key' => 'users']);

        $this->actingAs($manager)->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Calon User');
    }

    public function test_the_seeded_system_account_is_the_only_one_at_that_level(): void
    {
        $this->seed();

        $admin = User::where('username', 'superadmin')->firstOrFail();
        $this->assertTrue($admin->is_superadmin);
        $this->assertSame(0, $admin->companies()->count());
        $this->assertSame(1, User::where('is_superadmin', true)->count());
    }
}
