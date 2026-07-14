<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            ->assertJsonPath('user.role', 'agence')
            ->assertJsonPath('user.agence.nom', 'Transit Express');

        $this->assertDatabaseHas('users', [
            'email' => 'gerant@transit-express.test',
            'role' => 'agence',
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
                [
                    'fichier' => UploadedFile::fake()->image('rc.jpg'),
                    'type_document' => 'registre_commerce',
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('user.agence.nom', 'Transit Media')
            ->assertJsonPath('user.agence.logo.nom_original', 'logo.png')
            ->assertJsonPath('user.agence.documents.0.type_document', 'piece_identite')
            ->assertJsonPath('user.agence.documents.1.type_document', 'registre_commerce')
            ->assertJsonStructure([
                'user' => [
                    'agence' => [
                        'logo' => ['id', 'chemin', 'url', 'nom_original'],
                        'documents' => [
                            ['id', 'type_document', 'chemin', 'url', 'nom_original'],
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseCount('logos', 1);
        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseHas('documents', [
            'type_document' => 'piece_identite',
            'documentable_type' => 'agence',
        ]);
    }

    public function test_agence_register_rejects_duplicate_gerant_email(): void
    {
        ['user' => $user] = $this->createAgenceAccount();

        $response = $this->postJson('/api/v1/agence/register', [
            'nom' => 'Autre Agence',
            'email' => 'contact@autre-agence.test',
            'telephone' => '0699999999',
            'gerant_name' => 'Autre Gérant',
            'gerant_email' => $user->email,
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

        $response = $this->postJson('/api/v1/agence/login', [
            'email' => 'gerant@transit-express.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'gerant@transit-express.test');
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

    public function test_agence_me_includes_logo_and_documents(): void
    {
        Storage::fake('public');
        ['user' => $user, 'agence' => $agence] = $this->createAgenceAccount();

        $agence->logo()->create([
            'chemin' => "logos/{$agence->id}/logo.png",
            'nom_original' => 'logo.png',
        ]);

        $agence->documents()->create([
            'type_document' => 'piece_identite',
            'chemin' => "documents/agences/{$agence->id}/cni.pdf",
            'nom_original' => 'cni.pdf',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/agence/me')
            ->assertOk()
            ->assertJsonPath('data.agence.logo.nom_original', 'logo.png')
            ->assertJsonPath('data.agence.documents.0.type_document', 'piece_identite')
            ->assertJsonStructure([
                'data' => [
                    'agence' => [
                        'logo' => ['id', 'chemin', 'url', 'nom_original'],
                        'documents' => [
                            ['id', 'type_document', 'chemin', 'url', 'nom_original'],
                        ],
                    ],
                ],
            ]);
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
