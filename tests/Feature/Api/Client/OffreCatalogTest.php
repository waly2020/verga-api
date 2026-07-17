<?php

namespace Tests\Feature\Api\Client;

use App\Models\Offre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class OffreCatalogTest extends ClientApiTestCase
{
    use RefreshDatabase;

    private function createOffre(array $attributes = []): Offre
    {
        static $counter = 0;
        $counter++;

        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit Test',
            'email' => "agence{$counter}@test.com",
            'telephone' => '0611111111',
        ]);

        return Offre::create(array_merge([
            'agence_id' => $agence->id,
            'titre' => 'Groupage Paris',
            'type' => 'particulier',
            'prix' => 2500,
            'capacite_totale' => 1000,
            'capacite_disponible' => 500,
            'origine' => 'Libreville',
            'destination' => 'Paris',
            'statut' => 'active',
        ], $attributes));
    }

    public function test_lists_active_offres_with_pagination(): void
    {
        $this->createOffre(['titre' => 'Offre A']);
        $this->createOffre(['titre' => 'Offre B', 'destination' => 'Lyon']);

        $response = $this->getJson('/api/v1/client/offres?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'titre',
                    'description',
                    'type',
                    'type_offre_id',
                    'prix',
                    'capacite_totale',
                    'capacite_disponible',
                    'origine',
                    'destination',
                    'statut',
                    'created_at',
                    'agence',
                ]],
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_excludes_inactive_or_empty_stock_offres(): void
    {
        $active = $this->createOffre();
        $this->createOffre(['statut' => 'inactive']);
        $this->createOffre(['capacite_disponible' => 0, 'titre' => 'Pleine']);

        $this->getJson('/api/v1/client/offres')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_includes_unlimited_capacity_offres_without_stock(): void
    {
        $illimitee = $this->createOffre([
            'titre' => 'Illimitée',
            'capacite_illimitee' => true,
            'capacite_totale' => null,
            'capacite_disponible' => null,
        ]);
        $this->createOffre(['capacite_disponible' => 0, 'titre' => 'Pleine']);

        $this->getJson('/api/v1/client/offres')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $illimitee->id)
            ->assertJsonPath('data.0.capacite_illimitee', true)
            ->assertJsonPath('data.0.capacite_disponible', null);
    }

    public function test_filters_by_search(): void
    {
        $match = $this->createOffre(['titre' => 'Express Douala']);
        $this->createOffre(['titre' => 'Autre trajet', 'destination' => 'Marseille']);

        $this->getJson('/api/v1/client/offres?search=Douala')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_filters_by_destination(): void
    {
        $paris = $this->createOffre(['destination' => 'Paris']);
        $this->createOffre(['destination' => 'Lyon']);

        $this->getJson('/api/v1/client/offres?destination=Paris')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paris->id);
    }

    public function test_filters_by_type(): void
    {
        $conteneur = $this->createOffre(['type' => 'conteneur', 'titre' => 'Conteneur']);
        $this->createOffre(['type' => 'particulier']);

        $this->getJson('/api/v1/client/offres?type=conteneur')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conteneur->id);
    }

    public function test_filters_by_date_range(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $recent = $this->createOffre(['titre' => 'Récente']);
        $recent->forceFill(['created_at' => '2026-06-10'])->save();

        $old = $this->createOffre(['titre' => 'Ancienne']);
        $old->forceFill(['created_at' => '2026-05-01'])->save();

        $this->getJson('/api/v1/client/offres?date_debut=2026-06-01&date_fin=2026-06-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recent->id);

        Carbon::setTestNow();
    }

    public function test_rejects_invalid_date_range(): void
    {
        $this->getJson('/api/v1/client/offres?date_debut=2026-06-30&date_fin=2026-06-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_fin']);
    }
}
