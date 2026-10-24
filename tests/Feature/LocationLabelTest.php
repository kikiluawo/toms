<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The head office is a location of its own, so no screen may present it as
 * "a branch (pusat)".
 */
class LocationLabelTest extends TestCase
{
    use RefreshDatabase;

    private function companyWithLocations(): Company
    {
        $company = Company::create(['name' => 'PT. Majesty Prosperindo', 'code' => 'MP']);
        $company->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $company->branches()->create(['name' => 'Prosperindo Batam', 'code' => 'MP-BTM', 'is_head_office' => true]);

        return $company;
    }

    public function test_the_company_picker_lists_the_head_office_without_the_word_branch(): void
    {
        $company = $this->companyWithLocations();
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role' => 'manager', 'data_scope' => 'company']);

        $this->actingAs($user)->get(route('context.select'))
            ->assertOk()
            ->assertSee('Kantor Pusat')
            ->assertSee('Prosperindo Batam')
            ->assertSee('Cabang')
            ->assertDontSee('(pusat)', false);
    }

    public function test_the_head_office_comes_before_the_branches_in_the_picker(): void
    {
        $company = $this->companyWithLocations();
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role' => 'manager', 'data_scope' => 'company']);

        $html = $this->actingAs($user)->get(route('context.select'))->getContent();

        $this->assertLessThan(
            strpos($html, 'Prosperindo Jakarta'),
            strpos($html, 'Prosperindo Batam'),
            'Kantor pusat harus tampil sebelum daftar cabang.'
        );
    }

    public function test_the_user_form_groups_locations_by_head_office_and_branch(): void
    {
        $company = $this->companyWithLocations();
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('Pilih lokasi yang boleh diakses')
            ->assertSee('Kantor Pusat')
            ->assertSee('Prosperindo Batam')
            ->assertDontSee('Pusat</span>', false);
    }

    public function test_a_company_without_any_location_explains_the_gap(): void
    {
        Company::create(['name' => 'PT. Turbion Energy International', 'code' => 'TEI']);
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('belum punya lokasi, jadi user ini bekerja langsung');
    }

    public function test_the_master_pt_screen_splits_head_office_and_branch_sections(): void
    {
        $company = $this->companyWithLocations();
        $admin = User::factory()->create(['is_superadmin' => true]);

        $html = $this->actingAs($admin)->get(route('companies.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Kantor Pusat', $html);
        $this->assertStringContainsString('>Cabang<', $html);
        $this->assertLessThan(strpos($html, 'Cabang'), strpos($html, 'Kantor Pusat'));
        $this->assertStringNotContainsString('Pusat</span>', $html);
    }
}
