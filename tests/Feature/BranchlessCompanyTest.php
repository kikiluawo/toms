<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\UserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A PT without any location must still be usable: there is nothing to choose, so the
 * person works for the PT itself.
 */
class BranchlessCompanyTest extends TestCase
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

    private function makeStafAt(Company $company, string $username): User
    {
        $this->actingAs($this->superadmin())->post(route('users.store'), [
            'name' => 'Sinta Marketing',
            'username' => $username,
            'email' => "$username@oilms.test",
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'companies' => [$company->id => ['selected' => '1', 'role' => 'staf']],
        ])->assertSessionHasNoErrors();

        return User::where('username', $username)->firstOrFail();
    }

    public function test_a_branch_role_can_be_saved_at_a_company_without_locations(): void
    {
        $tei = $this->company('PT. Turbion Energy International', 'TEI');

        $sinta = $this->makeStafAt($tei, 'sinta');

        $this->assertDatabaseHas('company_user', [
            'user_id' => $sinta->id,
            'company_id' => $tei->id,
            'role' => 'staf',
            'data_scope' => 'branch',
        ]);
        $this->assertDatabaseCount('branch_user', 0);
    }

    public function test_the_form_says_the_person_works_for_the_whole_company(): void
    {
        $this->company('PT. Turbion Energy International', 'TEI');

        $this->actingAs($this->superadmin())->get(route('users.create'))
            ->assertOk()
            ->assertSee('bekerja langsung untuk PT tersebut');
    }

    public function test_a_branchless_user_can_open_his_company_and_reach_the_dashboard(): void
    {
        $tei = $this->company('PT. Turbion Energy International', 'TEI');
        $sinta = $this->makeStafAt($tei, 'sinta');

        $this->assertTrue(UserContext::for($sinta)->canActOn($tei->id));
        $this->assertSame([$tei->id], UserContext::for($sinta)->orderableCompanies()->keys()->all());

        $this->actingAs($sinta)->post(route('context.store'), ['company_id' => $tei->id])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($sinta)->get(route('dashboard'))->assertOk();
    }

    public function test_a_company_that_does_have_locations_still_demands_one(): void
    {
        $mp = $this->company('PT. Majesty Prosperindo', 'MP');
        $mp->branches()->create(['name' => 'Prosperindo Batam', 'code' => 'MP-BTM', 'is_head_office' => true]);

        $this->actingAs($this->superadmin())->post(route('users.store'), [
            'name' => 'Tanpa Lokasi',
            'username' => 'kosong',
            'email' => 'kosong@oilms.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'companies' => [$mp->id => ['selected' => '1', 'role' => 'staf']],
        ])->assertSessionHasErrors('branches');

        $this->assertDatabaseMissing('users', ['username' => 'kosong']);
    }

    public function test_the_first_location_is_given_to_people_hired_before_it_existed(): void
    {
        $tei = $this->company('PT. Turbion Energy International', 'TEI');
        $sinta = $this->makeStafAt($tei, 'sinta');

        $this->actingAs($this->superadmin())->post(route('companies.branches.store', $tei), [
            'name' => 'Turbion Batam',
            'code' => 'TEI-BTM',
            'is_head_office' => '1',
        ])->assertSessionHas('status');

        $branch = $tei->branches()->firstOrFail();

        $this->assertDatabaseHas('branch_user', ['user_id' => $sinta->id, 'branch_id' => $branch->id]);
        $this->assertTrue(UserContext::for($sinta->fresh())->canActOn($tei->id, $branch->id));
    }

    public function test_a_second_location_is_not_handed_out_to_everyone(): void
    {
        $tei = $this->company('PT. Turbion Energy International', 'TEI');
        $sinta = $this->makeStafAt($tei, 'sinta');

        $this->actingAs($this->superadmin())->post(route('companies.branches.store', $tei), [
            'name' => 'Turbion Batam',
            'code' => 'TEI-BTM',
            'is_head_office' => '1',
        ])->assertSessionHas('status');

        $this->actingAs($this->superadmin())->post(route('companies.branches.store', $tei), [
            'name' => 'Turbion Karimun',
            'code' => 'TEI-KRM',
        ])->assertSessionHas('status');

        $first = $tei->branches()->where('code', 'TEI-BTM')->firstOrFail();
        $second = $tei->branches()->where('code', 'TEI-KRM')->firstOrFail();

        $this->assertDatabaseHas('branch_user', ['user_id' => $sinta->id, 'branch_id' => $first->id]);
        $this->assertDatabaseMissing('branch_user', ['user_id' => $sinta->id, 'branch_id' => $second->id]);
    }

    public function test_a_branch_user_who_is_not_placed_anywhere_cannot_write(): void
    {
        $tei = $this->company('PT. Turbion Energy International', 'TEI');
        $tei->branches()->create(['name' => 'Turbion Batam', 'code' => 'TEI-BTM']);
        $sinta = User::factory()->create();
        $sinta->companies()->attach($tei->id, ['role' => 'staf', 'data_scope' => 'branch']);

        $this->assertFalse(UserContext::for($sinta)->canActOn($tei->id));
        $this->assertSame([], UserContext::for($sinta)->orderableCompanies()->keys()->all());
    }
}
