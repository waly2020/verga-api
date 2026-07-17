<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\AgenceRole;
use App\Models\AgenceUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    /**
     * @return array{user: AgenceUser, agence: Agence}
     */
    private function createAgenceAccount(array $agenceAttributes = []): array
    {
        return $this->createTestAgence(array_merge([
            'nom' => 'Transit Libreville',
            'email' => 'contact@transit-libreville.test',
            'telephone' => '0612345678',
            'statut' => 'actif',
        ], $agenceAttributes), [
            'name' => 'Gérant Libreville',
            'email' => 'gerant@transit-libreville.test',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_agence_can_register(): void
    {
        $response = $this->postJson('/api/v1/agence/register', [
            'nom' => 'Transit Express',
            'email' => 'contact@transit-express.test',
            'telephone' => '0612345678',
            'ville' => 'Libreville',
            'pays' => 'Gabon',
            'gerant_name' => 'Jean Mbaye',
            'gerant_email' => 'gerant@transit-express.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'agence' => ['id', 'nom', 'statut']],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role.slug', AgenceRole::SLUG_ADMIN_AGENCE)
            ->assertJsonPath('user.agence.nom', 'Transit Express');

        $this->assertDatabaseHas('agence_users', [
            'email' => 'gerant@transit-express.test',
            'est_proprietaire' => true,
        ]);
        $this->assertDatabaseHas('agences', [
            'email' => 'contact@transit-express.test',
            'statut' => 'actif',
        ]);
    }

    public function test_agence_can_register_with_logo_and_documents(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/v1/agence/register', [
            'nom' => 'Transit Media',
            'email' => 'contact@transit-media.test',
            'telephone' => '0612345678',
            'gerant_name' => 'Jean Mbaye',
            'gerant_email' => 'gerant@transit-media.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'documents' => [
                [
                    'fichier' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
                    'type_document' => 'piece_identite',
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('user.agence.nom', 'Transit Media')
            ->assertJsonPath('user.agence.logo.nom_original', 'logo.png');

        $this->assertDatabaseCount('logos', 1);
        $this->assertDatabaseCount('documents', 1);
    }

    public function test_agence_register_rejects_duplicate_gerant_email(): void
    {
        ['owner' => $owner] = $this->createAgenceAccount();

        $response = $this->postJson('/api/v1/agence/register', [
            'nom' => 'Autre Agence',
            'email' => 'contact@autre-agence.test',
            'telephone' => '0699999999',
            'gerant_name' => 'Autre Gérant',
            'gerant_email' => $owner->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gerant_email']);
    }

    public function test_registered_agence_can_login(): void
    {
        $this->postJson('/api/v1/agence/register', [
            'nom' => 'Transit Express',
            'email' => 'contact@transit-express.test',
            'telephone' => '0612345678',
            'gerant_name' => 'Jean Mbaye',
            'gerant_email' => 'gerant@transit-express.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $this->postJson('/api/v1/agence/login', [
            'email' => 'gerant@transit-express.test',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.email', 'gerant@transit-express.test');
    }

    public function test_agence_can_login_with_valid_credentials(): void
    {
        ['owner' => $owner] = $this->createAgenceAccount();

        $this->postJson('/api/v1/agence/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.role.slug', AgenceRole::SLUG_ADMIN_AGENCE);
    }

    public function test_agence_cannot_login_with_invalid_password(): void
    {
        ['owner' => $owner] = $this->createAgenceAccount();

        $this->postJson('/api/v1/agence/login', [
            'email' => $owner->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_client_cannot_login_via_agence_api(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/agence/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_blocked_agence_cannot_login(): void
    {
        ['owner' => $owner] = $this->createAgenceAccount(['statut' => 'bloqué']);

        $this->postJson('/api/v1/agence/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_authenticated_agence_can_view_profile(): void
    {
        ['owner' => $owner, 'agence' => $agence] = $this->createAgenceAccount();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/agence/me')
            ->assertOk()
            ->assertJsonPath('data.agence.id', $agence->id)
            ->assertJsonPath('data.email', $owner->email);
    }

    public function test_authenticated_agence_can_update_password(): void
    {
        ['owner' => $owner] = $this->createAgenceAccount();

        $this->actingAs($owner, 'sanctum')
            ->putJson('/api/v1/agence/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $owner->refresh();
        $this->assertTrue(Hash::check('new-password', $owner->password));
    }

    public function test_agence_can_logout(): void
    {
        ['owner' => $owner] = $this->createAgenceAccount();
        $token = $owner->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/agence/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
