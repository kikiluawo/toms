<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $mp;

    private Company $njkm;

    private User $director;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mp = Company::create(['name' => 'PT. Majesty Prosperindo', 'code' => 'MP']);
        $this->njkm = Company::create(['name' => 'PT. Nusa Jaya Karya Mandiri', 'code' => 'NJKM']);

        $this->mp->branches()->create(['name' => 'Prosperindo Jakarta', 'code' => 'MP-JKT']);
        $this->njkm->branches()->create(['name' => 'NJKM Bintan (Lobam)', 'code' => 'NJKM-BTN']);

        Department::create(['name' => 'EKSEKUTIF']);
        Position::create(['name' => 'CEO', 'department_id' => Department::where('name', 'EKSEKUTIF')->value('id')]);

        $mpMarketing = $this->mp->departments()->create(['name' => 'MARKETING']);
        $this->mp->positions()->create(['name' => 'Marketing', 'department_id' => $mpMarketing->id]);
        $this->njkm->departments()->create(['name' => 'OPERASIONAL']);

        $this->director = User::factory()->create();
        $this->director->companies()->attach($this->njkm, ['role' => 'ceo', 'data_scope' => 'group']);
        $this->director->menuPermissions()->create(['menu_key' => 'users']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@oilms.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'companies' => [
                $this->mp->id => [
                    'selected' => '1',
                    'role' => 'staf',
                    'department_id' => $this->mp->departments()->where('name', 'MARKETING')->value('id'),
                    'position_id' => $this->mp->positions()->where('name', 'Marketing')->value('id'),
                ],
            ],
            'branches' => [$this->mp->branches()->value('id')],
            'menus' => [],
        ], $overrides);
    }

    public function test_create_form_lists_every_active_company_for_a_group_user(): void
    {
        $this->actingAs($this->director)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('PT. Majesty Prosperindo')
            ->assertSee('PT. Nusa Jaya Karya Mandiri')
            ->assertSee('EKSEKUTIF');
    }

    public function test_director_can_create_a_user_with_a_role_per_company(): void
    {
        $this->actingAs($this->director)
            ->post(route('users.store'), $this->payload())
            ->assertRedirect(route('users.index'));

        $user = User::where('username', 'budi')->firstOrFail();
        $membership = $user->companies()->where('companies.id', $this->mp->id)->firstOrFail();

        $this->assertSame('staf', $membership->pivot->role);
        $this->assertSame('branch', $membership->pivot->data_scope);
        $this->assertSame(
            $this->mp->departments()->where('name', 'MARKETING')->value('id'),
            $membership->pivot->department_id
        );
        $this->assertFalse($user->context()->canActOn($this->mp->id));
        $this->assertTrue($user->context()->canActOn($this->mp->id, (int) $this->mp->branches()->value('id')));
        $this->assertSame([$this->mp->id], $user->context()->visibleCompanyIds());
    }

    public function test_group_role_stores_group_scope_and_needs_no_branch(): void
    {
        $payload = $this->payload([
            'companies' => [
                $this->mp->id => [
                    'selected' => '1',
                    'role' => 'coo',
                    'department_id' => Department::where('name', 'EKSEKUTIF')->value('id'),
                    'position_id' => Position::where('name', 'CEO')->value('id'),
                ],
            ],
            'branches' => [],
        ]);

        $this->actingAs($this->director)->post(route('users.store'), $payload)->assertRedirect();

        $user = User::where('username', 'budi')->firstOrFail();

        $this->assertSame('group', $user->companies()->firstOrFail()->pivot->data_scope);
        $this->assertSame(
            [$this->mp->id, $this->njkm->id],
            collect($user->context()->visibleCompanyIds())->sort()->values()->all()
        );
    }

    public function test_company_scope_role_does_not_require_a_branch(): void
    {
        $payload = $this->payload([
            'companies' => [
                $this->mp->id => [
                    'selected' => '1',
                    'role' => 'manager',
                    'department_id' => $this->mp->departments()->where('name', 'MARKETING')->value('id'),
                    'position_id' => $this->mp->positions()->where('name', 'Marketing')->value('id'),
                ],
            ],
            'branches' => [],
        ]);

        $this->actingAs($this->director)
            ->post(route('users.store'), $payload)
            ->assertSessionMissing('errors');

        $this->assertSame('company', User::where('username', 'budi')->firstOrFail()->companies()->firstOrFail()->pivot->data_scope);
    }

    public function test_branch_scope_role_requires_a_branch(): void
    {
        $this->actingAs($this->director)
            ->post(route('users.store'), $this->payload(['branches' => []]))
            ->assertSessionHasErrors('branches');
    }

    public function test_department_of_another_company_is_rejected(): void
    {
        $foreignDepartment = $this->njkm->departments()->where('name', 'OPERASIONAL')->value('id');

        $this->actingAs($this->director)
            ->post(route('users.store'), $this->payload([
                'companies' => [
                    $this->mp->id => [
                        'selected' => '1',
                        'role' => 'staf',
                        'department_id' => $foreignDepartment,
                        'position_id' => $this->mp->positions()->where('name', 'Marketing')->value('id'),
                    ],
                ],
            ]))
            ->assertSessionHasErrors('companies.'.$this->mp->id.'.department');
    }

    public function test_unknown_role_is_rejected(): void
    {
        $this->actingAs($this->director)
            ->post(route('users.store'), $this->payload([
                'companies' => [
                    $this->mp->id => [
                        'selected' => '1',
                        'role' => 'admin',
                        'department_id' => $this->mp->departments()->where('name', 'MARKETING')->value('id'),
                        'position_id' => $this->mp->positions()->where('name', 'Marketing')->value('id'),
                    ],
                ],
            ]))
            ->assertSessionHasErrors('companies.'.$this->mp->id.'.role');
    }

    public function test_a_role_is_mandatory_for_every_selected_company(): void
    {
        $this->actingAs($this->director)
            ->post(route('users.store'), $this->payload([
                'companies' => [
                    $this->mp->id => [
                        'selected' => '1',
                        'role' => '',
                        'department_id' => $this->mp->departments()->where('name', 'MARKETING')->value('id'),
                        'position_id' => $this->mp->positions()->where('name', 'Marketing')->value('id'),
                    ],
                ],
            ]))
            ->assertSessionHasErrors('companies.'.$this->mp->id.'.role');
    }

    public function test_a_company_scope_manager_cannot_touch_another_company(): void
    {
        $manager = User::factory()->create();
        $manager->companies()->attach($this->mp, ['role' => 'manager', 'data_scope' => 'company']);
        $manager->menuPermissions()->create(['menu_key' => 'users']);

        $this->actingAs($manager)
            ->post(route('users.store'), $this->payload([
                'companies' => [
                    $this->njkm->id => [
                        'selected' => '1',
                        'role' => 'staf',
                        'department_id' => $this->njkm->departments()->where('name', 'OPERASIONAL')->value('id'),
                    ],
                ],
                'branches' => [$this->njkm->branches()->value('id')],
            ]))
            ->assertSessionHasErrors('companies');

        $this->assertDatabaseMissing('users', ['username' => 'budi']);
    }

    public function test_a_branch_user_cannot_open_the_users_screen(): void
    {
        $staff = User::factory()->create();
        $branch = $this->mp->branches()->first();
        $staff->companies()->attach($this->mp, ['role' => 'staf', 'data_scope' => 'branch']);
        $staff->branches()->attach($branch);

        $this->actingAs($staff)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_updating_a_user_replaces_their_membership_scope(): void
    {
        $this->actingAs($this->director)->post(route('users.store'), $this->payload());
        $user = User::where('username', 'budi')->firstOrFail();

        $this->actingAs($this->director)
            ->put(route('users.update', $user), $this->payload([
                'companies' => [
                    $this->njkm->id => [
                        'selected' => '1',
                        'role' => 'manager',
                        'department_id' => $this->njkm->departments()->where('name', 'OPERASIONAL')->value('id'),
                    ],
                ],
                'branches' => [],
            ]))
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame([$this->njkm->id], $user->context()->visibleCompanyIds());
        $this->assertSame([], $user->branches()->pluck('branches.id')->all());
    }

    public function test_a_manager_cannot_promote_anyone_to_the_group_level(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('users.store'), $this->payload([
                'companies' => [
                    $this->mp->id => [
                        'selected' => '1',
                        'role' => 'coo',
                        'department_id' => Department::where('name', 'EKSEKUTIF')->value('id'),
                    ],
                ],
                'branches' => [],
            ]))
            ->assertSessionHasErrors('companies.'.$this->mp->id.'.role');

        $this->assertDatabaseMissing('users', ['username' => 'budi']);
    }

    public function test_a_manager_only_offers_roles_at_its_own_reach(): void
    {
        $this->actingAs($this->manager())
            ->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('value="coo"', false)
            ->assertDontSee('value="ceo"', false)
            ->assertSee('value="staf"', false);
    }

    public function test_a_manager_cannot_edit_or_delete_a_group_level_user(): void
    {
        $manager = $this->manager();

        $peer = User::factory()->create();
        $peer->companies()->attach($this->mp, ['role' => 'coo', 'data_scope' => 'group']);

        $this->actingAs($manager)->get(route('users.edit', $peer))->assertForbidden();
        $this->actingAs($manager)->delete(route('users.destroy', $peer))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $peer->id]);
    }

    public function test_a_group_user_can_still_grant_every_role(): void
    {
        $this->actingAs($this->director)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('value="coo"', false)
            ->assertSee('value="staf"', false);
    }

    private function manager(): User
    {
        $manager = User::factory()->create();
        $manager->companies()->attach($this->mp, ['role' => 'manager', 'data_scope' => 'company']);
        $manager->menuPermissions()->create(['menu_key' => 'users']);

        return $manager;
    }
}
