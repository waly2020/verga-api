<?php

namespace Tests\Feature\Api\Client;

use Illuminate\Foundation\Testing\RefreshDatabase;

class DestinationCatalogTest extends ClientApiTestCase
{
    use RefreshDatabase;

    public function test_lists_active_destinations_without_authentication(): void
    {
        $librevilleParis = $this->createDestination([
            'depart' => 'Libreville',
            'arrivee' => 'Paris',
        ]);
        $this->createDestination([
            'depart' => 'Douala',
            'arrivee' => 'Lyon',
        ]);
        $inactive = $this->createDestination([
            'depart' => 'Inactive',
            'arrivee' => 'Ville',
            'actif' => false,
        ]);

        $this->getJson('/api/v1/client/destinations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'ville_depart_id',
                    'ville_arrivee_id',
                    'ville_depart',
                    'ville_arrivee',
                    'label',
                    'actif',
                ]],
            ])
            ->assertJsonFragment(['id' => $librevilleParis->id])
            ->assertJsonMissing(['id' => $inactive->id])
            ->assertJsonMissingPath('data.0.rattachee');
    }

    public function test_filters_destinations_by_search(): void
    {
        $paris = $this->createDestination([
            'depart' => 'Libreville',
            'arrivee' => 'Paris',
        ]);
        $this->createDestination([
            'depart' => 'Douala',
            'arrivee' => 'Lyon',
        ]);

        $this->getJson('/api/v1/client/destinations?search=Paris')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paris->id);
    }
}
