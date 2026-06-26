<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, agence: Agence}
     */
    private function createAgenceAccount(array $agenceAttributes = []): array
    {
        $user = User::factory()->create([
            'role' => 'agence',
            'password' => Hash::make('password'),
        ]);

        $agence = Agence::create(array_merge([
            'user_id' => $user->id,
            'nom' => 'Transit Libreville',
            'email' => 'contact@transit-libreville.test',
            'telephone' => '0612345678',
            'statut' => 'actif',
        ], $agenceAttributes));

        return compact('user', 'agence');
    }

    public function test_agence_can_login_with_valid_credentials(): void
    {
        ['user' => $user] = $this->createAgenceAccount();

        $response = $this->postJson('/api/v1/agence/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'agence' => ['id', 'nom', 'statut']],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'agence');
    }

    public function test_agence_cannot_login_with_invalid_password(): void
    {
        ['user' => $user] = $this->createAgenceAccount();

        $response = $this->postJson('/api/v1/agence/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_client_cannot_login_via_agence_api(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/agence/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_blocked_agence_cannot_login(): void
    {
        ['user' => $user] = $this->createAgenceAccount(['statut' => 'bloqué']);

        $response = $this->postJson('/api/v1/agence/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Ce compte agence est bloqué.');
    }

    public function test_authenticated_agence_can_view_profile(): void
    {
        ['user' => $user, 'agence' => $agence] = $this->createAgenceAccount();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/agence/me');

        $response->assertOk()
            ->assertJsonPath('data.agence.id', $agence->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_authenticated_agence_can_update_password(): void
    {
        ['user' => $user] = $this->createAgenceAccount();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/agence/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Mot de passe mis à jour avec succès.');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_agence_cannot_update_password_with_wrong_current_password(): void
    {
        ['user' => $user] = $this->createAgenceAccount();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/agence/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_guest_cannot_access_protected_agence_routes(): void
    {
        $this->getJson('/api/v1/agence/me')->assertUnauthorized();
        $this->putJson('/api/v1/agence/password', [])->assertUnauthorized();
    }

    public function test_agence_can_logout(): void
    {
        ['user' => $user] = $this->createAgenceAccount();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/agence/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Déconnexion réussie.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
