<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AgenceRole;
use App\Models\AgenceUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class AgenceUserTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_strict_admin_can_manage_agence_collaborateurs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ['agence' => $agence] = $this->createTestAgence();
        $operations = $this->createRole('operations', 'Opérations');
        $finance = $this->createRole('finance', 'Finance');

        $create = $this->actingAs($admin)->post(route('admin.agence-users.store'), [
            'agence_id' => $agence->id,
            'agence_role_id' => $operations->id,
            'name' => 'Agent opérations',
            'email' => 'agent.operations@example.test',
            'telephone' => '0612345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'statut' => AgenceUser::STATUT_ACTIF,
        ]);

        $create->assertRedirect();

        $collaborateur = AgenceUser::query()
            ->where('email', 'agent.operations@example.test')
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.agence-users.update', $collaborateur), [
                'agence_role_id' => $finance->id,
                'name' => 'Agent finance',
                'email' => $collaborateur->email,
                'telephone' => $collaborateur->telephone,
                'statut' => AgenceUser::STATUT_SUSPENDU,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('agence_users', [
            'id' => $collaborateur->id,
            'agence_role_id' => $finance->id,
            'name' => 'Agent finance',
            'statut' => AgenceUser::STATUT_SUSPENDU,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.agence-users.destroy', $collaborateur))
            ->assertRedirect();

        $this->assertDatabaseMissing('agence_users', ['id' => $collaborateur->id]);
    }

    public function test_owner_cannot_be_modified_or_deleted_from_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ['owner' => $owner] = $this->createTestAgence();
        $role = $this->createRole('operations', 'Opérations');

        $this->actingAs($admin)
            ->patch(route('admin.agence-users.update', $owner), [
                'agence_role_id' => $role->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'telephone' => $owner->telephone,
                'statut' => AgenceUser::STATUT_SUSPENDU,
            ])
            ->assertSessionHasErrors('user');

        $this->actingAs($admin)
            ->delete(route('admin.agence-users.destroy', $owner))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('agence_users', [
            'id' => $owner->id,
            'est_proprietaire' => true,
            'statut' => AgenceUser::STATUT_ACTIF,
        ]);
    }

    public function test_internal_collaborator_cannot_access_agence_users_admin(): void
    {
        $collaborator = User::factory()->create(['role' => 'collaborateur']);

        $this->actingAs($collaborator)
            ->get(route('admin.agence-users.index'))
            ->assertForbidden();
    }

    private function createRole(string $slug, string $nom): AgenceRole
    {
        return AgenceRole::create([
            'slug' => $slug,
            'nom' => $nom,
            'actif' => true,
            'est_systeme' => false,
        ]);
    }
}
