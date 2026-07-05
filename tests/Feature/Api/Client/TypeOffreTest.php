<?php

namespace Tests\Feature\Api\Client;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TypeOffreTest extends ClientApiTestCase
{
    use RefreshDatabase;

    public function test_lists_all_types_offres_without_pagination(): void
    {
        $this->getJson('/api/v1/client/types-offres')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'slug', 'nom', 'unite_label', 'quantite_entier'],
                ],
            ])
            ->assertJsonMissing(['links', 'meta']);
    }

    public function test_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/client/types-offres')->assertOk();
    }
}
