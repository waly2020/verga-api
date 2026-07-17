<?php

namespace Tests\Feature\Admin;

use App\Models\AgenceRole;
use App\Models\AgenceUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgenceRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_strict_admin_can_manage_agence_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.agence-roles.store'), [
                'slug' => 'support',
                'nom' => 'Support client',
                'description' => 'Assistance clientèle',
                'actif' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('agence_roles', [
            'slug' => 'support',
            'nom' => 'Support client',
        ]);
    }

    public function test_collaborator_cannot_manage_agence_roles(): void
    {
        $collaborator = User::factory()->create(['role' => 'collaborateur']);

        $this->actingAs($collaborator)
            ->get(route('admin.agence-roles.index'))
            ->assertForbidden();
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $role = AgenceRole::query()->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.agence-roles.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseHas('agence_roles', ['id' => $role->id]);
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $role = AgenceRole::create([
            'slug' => 'operations',
            'nom' => 'Opérations',
            'description' => 'Suivi logistique et colis.',
            'actif' => true,
            'est_systeme' => false,
        ]);

        AgenceUser::factory()->create([
            'agence_role_id' => $role->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.agence-roles.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseHas('agence_roles', ['id' => $role->id]);
    }
}
