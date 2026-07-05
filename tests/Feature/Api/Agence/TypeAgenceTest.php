<?php

namespace Tests\Feature\Api\Agence;

use App\Models\TypeAgence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TypeAgenceTest extends AgenceApiTestCase
{
    use RefreshDatabase;

    public function test_lists_all_types_agences_without_pagination(): void
    {
        TypeAgence::create(['nom' => 'Transitaire', 'description' => 'Transit international']);
        TypeAgence::create(['nom' => 'Déménageur', 'description' => null]);

        $response = $this->getJson('/api/v1/agence/types-agences')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'nom', 'description'],
                ],
            ])
            ->assertJsonMissing(['links', 'meta']);

        $this->assertEquals('Déménageur', $response->json('data.0.nom'));
        $this->assertEquals('Transitaire', $response->json('data.1.nom'));
    }

    public function test_returns_empty_array_when_no_types(): void
    {
        $this->getJson('/api/v1/agence/types-agences')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_does_not_require_authentication(): void
    {
        TypeAgence::create(['nom' => 'Freight']);

        $this->getJson('/api/v1/agence/types-agences')->assertOk();
    }
}
