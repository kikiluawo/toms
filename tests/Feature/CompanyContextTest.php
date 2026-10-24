<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\UserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyContextTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name, string $code): Company
    {
        return Company::create(['name' => $name, 'code' => $code]);
    }

    public function test_user_with_multiple_companies_is_redirected_to_company_selection(): void
    {
        $user = User::factory()->create();
        $first = $this->company('PT. Majesty Prosperindo', 'MP');
        $second = $this->company('PT. Turbion Energy International', 'TEI');

        $user->companies()->attach($first, ['role' => 'manager', 'data_scope' => 'company']);
        $user->companies()->attach($second, ['role' => 'manager', 'data_scope' => 'company']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('context.select'));
    }

    public function test_user_with_single_company_gets_context_automatically(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $user->companies()->attach($company, ['role' => 'manager', 'data_scope' => 'company']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('PT. Majesty Prosperindo')
            ->assertSessionHas('active_company_id', $company->id);
    }

    public function test_company_selection_page_is_rendered(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');
        $company->branches()->create(['name' => 'NJKM Jakarta (Kijang)', 'code' => 'NJKM-JKT']);
        $user->companies()->attach($company, ['role' => 'manager', 'data_scope' => 'company']);

        $this->actingAs($user)
            ->get(route('context.select'))
            ->assertOk()
            ->assertSee('PT. Nusa Jaya Karya Mandiri')
            ->assertSee('NJKM Jakarta (Kijang)');
    }

    public function test_user_can_select_a_company_and_branch(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $branch = $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $user->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);
        $user->branches()->attach($branch);

        $this->actingAs($user)
            ->post(route('context.store'), [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($company->id, session('active_company_id'));
        $this->assertSame($branch->id, session('active_branch_id'));
    }

    public function test_user_cannot_select_a_company_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Turbion Energy International', 'TEI');

        $this->actingAs($user)
            ->post(route('context.store'), ['company_id' => $company->id])
            ->assertForbidden();
    }

    public function test_restricted_user_cannot_select_an_unassigned_branch(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $allowed = $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $blocked = $company->branches()->create(['name' => 'Prosperindo Karimun', 'code' => 'MP-KRM']);

        $user->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);
        $user->branches()->attach($allowed);

        $this->actingAs($user)
            ->post(route('context.store'), [
                'company_id' => $company->id,
                'branch_id' => $blocked->id,
            ])
            ->assertForbidden();
    }

    public function test_restricted_user_can_select_an_assigned_branch(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $allowed = $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $company->branches()->create(['name' => 'Prosperindo Karimun', 'code' => 'MP-KRM']);

        $user->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);
        $user->branches()->attach($allowed);

        $this->actingAs($user)
            ->post(route('context.store'), [
                'company_id' => $company->id,
                'branch_id' => $allowed->id,
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_selection_page_only_lists_assigned_branches_for_restricted_users(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $allowed = $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $company->branches()->create(['name' => 'Prosperindo Karimun', 'code' => 'MP-KRM']);

        $user->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);
        $user->branches()->attach($allowed);

        $this->actingAs($user)
            ->get(route('context.select'))
            ->assertOk()
            ->assertSee('Prosperindo Jakarta')
            ->assertDontSee('Prosperindo Karimun');
    }

    public function test_restricted_user_cannot_act_at_company_level(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);

        $user->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);

        $this->actingAs($user)
            ->post(route('context.store'), ['company_id' => $company->id])
            ->assertForbidden();
    }

    public function test_group_user_defaults_to_every_active_company_without_choosing(): void
    {
        $user = User::factory()->create();
        $anchor = $this->company('PT. Turbion Energy International', 'TEI');
        $other = $this->company('PT. Majesty Prosperindo', 'MP');

        $user->companies()->attach($anchor->id, ['role' => 'ceo', 'data_scope' => 'group']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSessionHas('active_company_id', UserContext::ALL)
            ->assertSee($anchor->name)
            ->assertSee($other->name);
    }

    public function test_group_user_may_open_a_company_they_are_not_a_member_of(): void
    {
        $user = User::factory()->create();
        $anchor = $this->company('PT. Turbion Energy International', 'TEI');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');
        $branch = $foreign->branches()->create(['name' => 'NJKM Bintan (Lobam)', 'code' => 'NJKM-BTN']);

        $user->companies()->attach($anchor->id, ['role' => 'cfo', 'data_scope' => 'group']);

        $this->actingAs($user)
            ->post(route('context.store'), ['company_id' => $foreign->id, 'branch_id' => $branch->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($foreign->id, session('active_company_id'));
        $this->assertSame($branch->id, session('active_branch_id'));
    }

    public function test_only_a_group_user_can_select_every_company(): void
    {
        $staff = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $staff->companies()->attach($company, ['role' => 'staf', 'data_scope' => 'branch']);
        $staff->branches()->attach($company->branches()->value('id'));

        $this->actingAs($staff)
            ->post(route('context.store'), ['company_id' => UserContext::ALL])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('context.select'))
            ->assertDontSee('Buka semua PT');
    }

    public function test_inactive_company_is_hidden_from_everyone(): void
    {
        $user = User::factory()->create();
        $anchor = $this->company('PT. Turbion Energy International', 'TEI');
        $closed = $this->company('PT. Mati Total', 'MT');
        $closed->update(['is_active' => false]);

        $user->companies()->attach($anchor->id, ['role' => 'ceo', 'data_scope' => 'group']);

        $this->actingAs($user)
            ->get(route('context.select'))
            ->assertOk()
            ->assertDontSee('PT. Mati Total');

        $this->actingAs($user)
            ->post(route('context.store'), ['company_id' => $closed->id])
            ->assertForbidden();
    }

    public function test_company_scope_user_cannot_reach_another_company(): void
    {
        $user = User::factory()->create();
        $home = $this->company('PT. Majesty Prosperindo', 'MP');
        $foreign = $this->company('PT. Nusa Jaya Karya Mandiri', 'NJKM');

        $user->companies()->attach($home->id, ['role' => 'manager', 'data_scope' => 'company']);

        $this->actingAs($user)
            ->post(route('context.store'), ['company_id' => $foreign->id])
            ->assertForbidden();
    }
}
