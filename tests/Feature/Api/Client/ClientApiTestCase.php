<?php

namespace Tests\Feature\Api\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

abstract class ClientApiTestCase extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    /**
     * @return array{user: User, client: Client, token: string}
     */
    protected function createAuthenticatedClient(array $clientAttributes = []): array
    {
        $user = User::factory()->create([
            'role' => 'client',
            'password' => Hash::make('password'),
        ]);

        $client = Client::create(array_merge([
            'user_id' => $user->id,
            'nom' => 'Mba',
            'prenom' => 'Paul',
            'email' => $user->email,
            'telephone' => '0612345678',
            'statut' => 'actif',
        ], $clientAttributes));

        $token = $user->createToken('test')->plainTextToken;

        return compact('user', 'client', 'token');
    }

    protected function withClientToken(string $token): static
    {
        return $this->withToken($token);
    }
}
