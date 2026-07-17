<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Agence;

use App\Models\AgenceRole;
use App\Models\AgenceUser;
use Illuminate\Support\Facades\Hash;

class AgenceUserTest extends AgenceApiTestCase
{
    public function test_register_creates_owner_with_admin_agence_role(): void
    {
        $this->postJson('/api/v1/agence/register', [
            'nom' => 'Transit Express',
            'email' => 'contact@transit-express.test',
            'telephone' => '0611111111',
            'gerant_name' => 'Jean Gérant',
            'gerant_email' => 'gerant@transit-express.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('agence_users', [
            'email' => 'gerant@transit-express.test',
            'est_proprietaire' => true,
        ]);

        $roleId = AgenceRole::query()->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)->value('id');

        $this->assertDatabaseHas('agence_users', [
            'email' => 'gerant@transit-express.test',
            'agence_role_id' => $roleId,
        ]);
    }

    public function test_me_returns_role_without_permissions(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/me')
            ->assertOk()
            ->assertJsonPath('data.est_proprietaire', true)
            ->assertJsonPath('data.role.slug', AgenceRole::SLUG_ADMIN_AGENCE)
            ->assertJsonMissingPath('data.permissions');
    }

    public function test_owner_can_manage_agence_users(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();
        $operationsRoleId = $this->createAgenceRole('operations')->id;

        $create = $this->withAgenceToken($token)->postJson('/api/v1/agence/users', [
            'name' => 'Agent Ops',
            'email' => 'ops@transit-libreville.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agence_role_id' => $operationsRoleId,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.role.slug', 'operations');

        $userId = $create->json('data.id');

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/users')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $financeRoleId = $this->createAgenceRole('finance')->id;

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/users/{$userId}", [
                'agence_role_id' => $financeRoleId,
            ])
            ->assertOk()
            ->assertJsonPath('data.role.slug', 'finance');

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/users/{$userId}")
            ->assertOk();

        $this->assertDatabaseMissing('agence_users', ['id' => $userId]);
    }

    public function test_owner_cannot_remove_itself(): void
    {
        ['user' => $owner, 'token' => $token] = $this->createAuthenticatedAgence();

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/users/{$owner->id}")
            ->assertUnprocessable();
    }

    public function test_system_owner_role_cannot_be_assigned_to_a_collaborator(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();
        $systemRole = AgenceRole::query()
            ->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)
            ->firstOrFail();

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/roles')
            ->assertOk()
            ->assertJsonMissing(['slug' => AgenceRole::SLUG_ADMIN_AGENCE]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/users', [
                'name' => 'Faux propriétaire',
                'email' => 'faux.proprietaire@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'agence_role_id' => $systemRole->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('agence_role_id');
    }

    public function test_all_roles_have_same_api_access(): void
    {
        ['agence' => $agence] = $this->createAuthenticatedAgence();

        foreach (['operations', 'commercial', 'finance'] as $roleSlug) {
            ['token' => $token] = $this->createAgenceAgent($agence, $roleSlug);

            $this->withAgenceToken($token)->getJson('/api/v1/agence/dashboard')->assertOk();
            $this->withAgenceToken($token)->getJson('/api/v1/agence/offres')->assertOk();
            $this->withAgenceToken($token)->getJson('/api/v1/agence/paiements')->assertOk();
        }
    }

    public function test_agent_can_login(): void
    {
        ['agence' => $agence] = $this->createAuthenticatedAgence();

        AgenceUser::create([
            'agence_id' => $agence->id,
            'agence_role_id' => $this->createAgenceRole('operations')->id,
            'name' => 'Agent Ops',
            'email' => 'agent@transit-libreville.test',
            'password' => Hash::make('password'),
            'statut' => AgenceUser::STATUT_ACTIF,
            'est_proprietaire' => false,
        ]);

        $this->postJson('/api/v1/agence/login', [
            'email' => 'agent@transit-libreville.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.role.slug', 'operations');
    }

    public function test_suspended_user_cannot_access_api(): void
    {
        ['agence' => $agence] = $this->createAuthenticatedAgence();
        ['token' => $token, 'user' => $user] = $this->createAgenceAgent($agence, 'operations');

        $user->update(['statut' => AgenceUser::STATUT_SUSPENDU]);

        $this->withAgenceToken($token)->getJson('/api/v1/agence/me')->assertForbidden();
    }

    public function test_user_isolation_between_agences(): void
    {
        ['agence' => $agenceA, 'token' => $tokenA] = $this->createAuthenticatedAgence();
        ['agence' => $agenceB] = $this->createAuthenticatedAgence([
            'email' => 'contact-b@transit.test',
            'telephone' => '0633333333',
        ], [
            'email' => 'gerant-b@transit.test',
        ]);

        ['user' => $userB] = $this->createAgenceAgent($agenceB, 'operations', [
            'email' => 'agent-b@transit.test',
        ]);

        $this->withAgenceToken($tokenA)
            ->patchJson("/api/v1/agence/users/{$userB->id}", [
                'name' => 'Hack',
            ])
            ->assertNotFound();
    }
}
