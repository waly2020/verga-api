<?php

namespace Tests\Feature\Api\Agence;

use App\Models\TypeOffre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TypeOffreTest extends AgenceApiTestCase
{
    use RefreshDatabase;

    public function test_lists_all_types_offres_without_pagination(): void
    {
        $this->getJson('/api/v1/agence/types-offres')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'slug', 'nom', 'description', 'unite', 'unite_label', 'quantite_entier', 'quantite_min'],
                ],
            ])
            ->assertJsonMissing(['links', 'meta']);

        $slugs = collect($this->getJson('/api/v1/agence/types-offres')->json('data'))
            ->pluck('slug')
            ->all();

        $this->assertEqualsCanonicalizing(
            ['particulier', 'metre_cube', 'conteneur'],
            $slugs
        );
    }

    public function test_excludes_inactive_types(): void
    {
        TypeOffre::query()->where('slug', 'conteneur')->update(['actif' => false]);

        $this->getJson('/api/v1/agence/types-offres')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/agence/types-offres')->assertOk();
    }
}
