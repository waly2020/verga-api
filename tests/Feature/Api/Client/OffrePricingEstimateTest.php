<?php

namespace Tests\Feature\Api\Client;

use App\Models\Agence;
use App\Models\ConfigurationCommission;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OffrePricingEstimateTest extends ClientApiTestCase
{
    use RefreshDatabase;

    private function createActiveOffre(float $prix = 2500, float $capacite = 100): Offre
    {
        $user = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $user->id,
            'nom' => 'Transit Test',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        return Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Groupage Paris',
            'type' => 'particulier',
            'prix' => $prix,
            'capacite_totale' => $capacite,
            'capacite_disponible' => $capacite,
            'origine' => 'Libreville',
            'destination' => 'Paris',
            'statut' => 'active',
        ]);
    }

    public function test_guest_can_estimate_pricing_without_commission(): void
    {
        $offre = $this->createActiveOffre();

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=10")
            ->assertOk()
            ->assertJsonPath('offre_id', $offre->id)
            ->assertJsonPath('quantite', 10)
            ->assertJsonPath('prix_unitaire', 2500)
            ->assertJsonPath('montant_sous_total', 25000)
            ->assertJsonPath('montant_commission_client', 0)
            ->assertJsonPath('montant_total', 25000)
            ->assertJsonPath('stock_suffisant', true)
            ->assertJsonPath('commission', null);
    }

    public function test_estimate_includes_percentage_commission(): void
    {
        $offre = $this->createActiveOffre();

        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 5,
            'libelle' => 'Frais de service',
            'actif' => true,
        ]);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=10")
            ->assertOk()
            ->assertJsonPath('montant_sous_total', 25000)
            ->assertJsonPath('montant_commission_client', 1250)
            ->assertJsonPath('montant_total', 26250)
            ->assertJsonPath('commission.type', 'pourcentage')
            ->assertJsonPath('commission.valeur', 5)
            ->assertJsonPath('commission.libelle', 'Frais de service');
    }

    public function test_estimate_includes_fixed_commission(): void
    {
        $offre = $this->createActiveOffre(8750, 50);

        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'fixe',
            'valeur' => 500,
            'actif' => true,
        ]);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=2")
            ->assertOk()
            ->assertJsonPath('montant_sous_total', 17500)
            ->assertJsonPath('montant_commission_client', 500)
            ->assertJsonPath('montant_total', 18000)
            ->assertJsonPath('commission.type', 'fixe');
    }

    public function test_estimate_flags_insufficient_stock(): void
    {
        $offre = $this->createActiveOffre(2500, 5);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=10")
            ->assertOk()
            ->assertJsonPath('capacite_disponible', 5)
            ->assertJsonPath('stock_suffisant', false);
    }

    public function test_estimate_requires_quantite(): void
    {
        $offre = $this->createActiveOffre();

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantite']);
    }

    public function test_estimate_returns_404_for_inactive_offre(): void
    {
        $offre = $this->createActiveOffre();
        $offre->update(['statut' => 'inactive']);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=10")
            ->assertNotFound();
    }
}
