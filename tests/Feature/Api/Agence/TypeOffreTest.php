<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Offre;
use App\Models\TypeOffre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TypeOffreTest extends AgenceApiTestCase
{
    use RefreshDatabase;

    public function test_guest_lists_only_platform_types_offres(): void
    {
        ['agence' => $agence] = $this->createAuthenticatedAgence();

        TypeOffre::create([
            'agence_id' => $agence->id,
            'slug' => 'palette',
            'nom' => 'Palette',
            'unite' => 'palette',
            'unite_label' => 'par palette',
            'quantite_entier' => true,
            'quantite_min' => 1,
            'actif' => true,
        ]);

        $this->getJson('/api/v1/agence/types-offres')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['links', 'meta']);

        $slugs = collect($this->getJson('/api/v1/agence/types-offres')->json('data'))
            ->pluck('slug')
            ->all();

        $this->assertEqualsCanonicalizing(
            ['particulier', 'metre_cube', 'conteneur'],
            $slugs
        );
    }

    public function test_authenticated_agence_lists_platform_and_own_types(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        TypeOffre::create([
            'agence_id' => $agence->id,
            'slug' => 'palette',
            'nom' => 'Palette',
            'unite' => 'palette',
            'unite_label' => 'par palette',
            'quantite_entier' => true,
            'quantite_min' => 1,
            'actif' => false,
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/types-offres')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment([
                'slug' => 'palette',
                'is_platform' => false,
                'actif' => false,
            ]);
    }

    public function test_agence_can_crud_own_type_offre(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $create = $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/types-offres', [
                'slug' => 'palette',
                'nom' => 'Palette standard',
                'description' => 'Transport par palette',
                'unite' => 'palette',
                'unite_label' => 'par palette',
                'quantite_entier' => true,
                'quantite_min' => 1,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.slug', 'palette')
            ->assertJsonPath('data.agence_id', $agence->id)
            ->assertJsonPath('data.is_platform', false);

        $typeId = $create->json('data.id');

        $this->withAgenceToken($token)
            ->getJson("/api/v1/agence/types-offres/{$typeId}")
            ->assertOk()
            ->assertJsonPath('data.nom', 'Palette standard');

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/types-offres/{$typeId}", [
                'nom' => 'Palette euro',
                'actif' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.nom', 'Palette euro')
            ->assertJsonPath('data.actif', false);

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/types-offres/{$typeId}")
            ->assertOk()
            ->assertJsonPath('message', 'Type d\'offre supprimé avec succès.');

        $this->assertDatabaseMissing('types_offres', ['id' => $typeId]);
    }

    public function test_agence_can_create_offre_with_custom_type(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $type = TypeOffre::create([
            'agence_id' => $agence->id,
            'slug' => 'palette',
            'nom' => 'Palette',
            'unite' => 'palette',
            'unite_label' => 'par palette',
            'quantite_entier' => true,
            'quantite_min' => 1,
            'actif' => true,
        ]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'titre' => 'Groupage palettes',
                'type_offre_id' => $type->id,
                'prix' => 15000,
                'capacite_totale' => 50,
                'origine' => 'Paris',
                'destination' => 'Libreville',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type_offre.slug', 'palette');
    }

    public function test_agence_cannot_modify_platform_type(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();

        $platform = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/types-offres/{$platform->id}", ['nom' => 'Hack'])
            ->assertForbidden();

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/types-offres/{$platform->id}")
            ->assertForbidden();
    }

    public function test_agence_cannot_access_other_agency_type(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();

        $otherUser = \App\Models\User::factory()->create(['role' => 'agence']);
        $otherAgence = \App\Models\Agence::create([
            'user_id' => $otherUser->id,
            'nom' => 'Autre Transit',
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '0699999999',
            'statut' => 'actif',
        ]);

        $foreignType = TypeOffre::create([
            'agence_id' => $otherAgence->id,
            'slug' => 'roll',
            'nom' => 'Roll',
            'unite' => 'roll',
            'unite_label' => 'par roll',
            'quantite_entier' => true,
            'quantite_min' => 1,
            'actif' => true,
        ]);

        $this->withAgenceToken($token)
            ->getJson("/api/v1/agence/types-offres/{$foreignType->id}")
            ->assertForbidden();
    }

    public function test_agence_cannot_delete_type_used_by_offre(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $type = TypeOffre::create([
            'agence_id' => $agence->id,
            'slug' => 'palette',
            'nom' => 'Palette',
            'unite' => 'palette',
            'unite_label' => 'par palette',
            'quantite_entier' => true,
            'quantite_min' => 1,
            'actif' => true,
        ]);

        Offre::create([
            'agence_id' => $agence->id,
            'type_offre_id' => $type->id,
            'titre' => 'Offre test',
            'type' => 'palette',
            'prix' => 1000,
            'capacite_totale' => 10,
            'capacite_disponible' => 10,
            'origine' => 'A',
            'destination' => 'B',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/types-offres/{$type->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type_offre']);
    }

    public function test_excludes_inactive_platform_types_for_guests(): void
    {
        TypeOffre::query()->where('slug', 'conteneur')->update(['actif' => false]);

        $this->getJson('/api/v1/agence/types-offres')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
