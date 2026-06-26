<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class AgenceApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, agence: Agence, token: string}
     */
    protected function createAuthenticatedAgence(array $agenceAttributes = []): array
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

        $token = $user->createToken('test')->plainTextToken;

        return compact('user', 'agence', 'token');
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
