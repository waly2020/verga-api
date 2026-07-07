<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Reversement;

class ReversementTest extends AgenceApiTestCase
{
    public function test_reversements_list_requires_authentication(): void
    {
        $this->getJson('/api/v1/agence/reversements')
            ->assertUnauthorized();
    }

    public function test_agence_can_list_its_reversements(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-06',
            'statut' => 'effectué',
            'effectue_le' => now(),
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 5000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/reversements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'montant', 'periode', 'statut', 'effectue_le', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_agence_can_filter_reversements_by_statut_and_periode(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-06',
            'statut' => 'effectué',
            'effectue_le' => now(),
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 5000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/reversements?statut=en_attente&periode=2026-07')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.montant', 5000)
            ->assertJsonPath('data.0.statut', 'en_attente');
    }

    public function test_reversements_list_is_isolated_between_agences(): void
    {
        ['token' => $tokenA] = $this->createAuthenticatedAgence();
        ['agence' => $agenceB] = $this->createAuthenticatedAgence([
            'email' => 'autre-agence@test.com',
            'nom' => 'Autre Agence',
        ]);

        Reversement::create([
            'agence_id' => $agenceB->id,
            'montant' => 10000,
            'periode' => '2026-06',
            'statut' => 'effectué',
            'effectue_le' => now(),
        ]);

        $this->withAgenceToken($tokenA)
            ->getJson('/api/v1/agence/reversements')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
