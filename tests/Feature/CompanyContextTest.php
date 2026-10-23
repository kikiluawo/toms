<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
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

        $user->companies()->attach($first, ['role' => 'admin', 'all_branches' => true]);
        $user->companies()->attach($second, ['role' => 'admin', 'all_branches' => true]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('context.select'));
    }

    public function test_user_with_single_company_gets_context_automatically(): void
    {
        $user = User::factory()->create();
        $company = $this->company('PT. Majesty Prosperindo', 'MP');
        $user->companies()->attach($company, ['role' => 'admin', 'all_branches' => true]);

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
        $user->companies()->attach($company, ['role' => 'admin', 'all_branches' => true]);

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
        $user->companies()->attach($company, ['role' => 'admin', 'all_branches' => true]);
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

        $user->companies()->attach($company, ['role' => 'staff', 'all_branches' => false]);
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

        $user->companies()->attach($company, ['role' => 'staff', 'all_branches' => false]);
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

        $user->companies()->attach($company, ['role' => 'staff', 'all_branches' => false]);
        $user->branches()->attach($allowed);

        $this->actingAs($user)
            ->get(route('context.select'))
            ->assertOk()
            ->assertSee('Prosperindo Jakarta')
            ->assertDontSee('Prosperindo Karimun');
    }
}
