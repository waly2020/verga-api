<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\AgenceRole;
use App\Models\AgenceUser;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

abstract class AgenceApiTestCase extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    /**
     * @return array{user: AgenceUser, agence: Agence, token: string}
     */
    protected function createAuthenticatedAgence(array $agenceAttributes = [], array $ownerAttributes = []): array
    {
        ['agence' => $agence, 'owner' => $user] = $this->createTestAgence(array_merge([
            'nom' => 'Transit Libreville',
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '0612345678',
        ], $agenceAttributes), array_merge([
            'name' => 'Gérant Libreville',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ], $ownerAttributes));

        $token = $user->createToken('test')->plainTextToken;

        return compact('user', 'agence', 'token');
    }

    /**
     * @return array{user: AgenceUser, agence: Agence, token: string}
     */
    protected function createAgenceAgent(Agence $agence, string $roleSlug, array $userAttributes = []): array
    {
        $roleId = $this->createAgenceRole($roleSlug)->id;

        $user = AgenceUser::create(array_merge([
            'agence_id' => $agence->id,
            'agence_role_id' => $roleId,
            'name' => 'Agent Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'statut' => AgenceUser::STATUT_ACTIF,
            'est_proprietaire' => false,
        ], $userAttributes));

        $token = $user->createToken('test')->plainTextToken;

        return compact('user', 'agence', 'token');
    }

    protected function createAgenceRole(string $slug): AgenceRole
    {
        return AgenceRole::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'nom' => ucfirst($slug),
                'description' => "Rôle {$slug} créé pour les tests.",
                'actif' => true,
                'est_systeme' => false,
            ],
        );
    }

    protected function createClient(): Client
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Client::create([
            'user_id' => $user->id,
            'nom' => 'Client',
            'prenom' => 'Test',
            'email' => $user->email,
            'telephone' => '0600000000',
            'statut' => 'actif',
        ]);
    }

    protected function withAgenceToken(string $token): static
    {
        return $this->withToken($token);
    }
}
