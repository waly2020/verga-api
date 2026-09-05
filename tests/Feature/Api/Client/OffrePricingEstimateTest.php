<?php

namespace Tests\Feature\Api\Client;

use App\Models\ConfigurationCommission;
use App\Models\Offre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OffrePricingEstimateTest extends ClientApiTestCase
{
    use RefreshDatabase;

    private function createActiveOffre(float $prix = 2500, float $capacite = 100): Offre
    {
        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit Test',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
        ]);

        return $this->createOffreForAgence($agence, [
            'titre' => 'Groupage Paris',
            'type' => 'particulier',
            'prix' => $prix,
            'capacite_totale' => $capacite,
            'capacite_disponible' => $capacite,
            'depart' => 'Libreville',
            'arrivee' => 'Paris',
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

    public function test_estimate_uses_grille_tranche_and_nullable_libelle(): void
    {
        $offre = $this->createActiveOffre(3000, 50);

        $config = ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'grille',
            'valeur' => 0,
            'libelle' => 'Frais VERGA',
            'actif' => true,
        ]);
        $config->paliers()->createMany([
            ['montant_min' => 0, 'montant_max' => 9999, 'frais' => 1500, 'libelle' => null],
            ['montant_min' => 10000, 'montant_max' => null, 'frais' => 2500, 'libelle' => 'Frais Dossier'],
        ]);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=3")
            ->assertOk()
            ->assertJsonPath('montant_sous_total', 9000)
            ->assertJsonPath('montant_commission_client', 1500)
            ->assertJsonPath('montant_total', 10500)
            ->assertJsonPath('commission.type', 'grille')
            ->assertJsonPath('commission.valeur', 1500)
            ->assertJsonPath('commission.libelle', null);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=4")
            ->assertOk()
            ->assertJsonPath('montant_sous_total', 12000)
            ->assertJsonPath('montant_commission_client', 2500)
            ->assertJsonPath('commission.libelle', 'Frais Dossier');
    }

    public function test_estimate_flags_insufficient_stock(): void
    {
        $offre = $this->createActiveOffre(2500, 5);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=10")
            ->assertOk()
            ->assertJsonPath('capacite_disponible', 5)
            ->assertJsonPath('stock_suffisant', false);
    }

    public function test_estimate_accepts_high_quantity_for_unlimited_offre(): void
    {
        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit Illimité',
            'email' => 'illimite@test.com',
            'telephone' => '0611111112',
        ]);

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre illimitée',
            'type' => 'particulier',
            'prix' => 2000,
            'capacite_illimitee' => true,
            'capacite_totale' => null,
            'capacite_disponible' => null,
            'depart' => 'Libreville',
            'arrivee' => 'Port-Gentil',
            'statut' => 'active',
        ]);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=500")
            ->assertOk()
            ->assertJsonPath('montant_sous_total', 1000000)
            ->assertJsonPath('capacite_disponible', null)
            ->assertJsonPath('stock_suffisant', true);
    }

    public function test_estimate_uses_palier_unit_price(): void
    {
        $offre = $this->createActiveOffre(3000);
        $offre->update([
            'paliers' => [
                ['min' => 1, 'max' => 2, 'prix' => 3000],
                ['min' => 3, 'max' => null, 'prix' => 2000],
            ],
        ]);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=5")
            ->assertOk()
            ->assertJsonPath('prix_unitaire', 2000)
            ->assertJsonPath('montant_sous_total', 10000)
            ->assertJsonPath('montant_total', 10000);
    }

    public function test_estimate_rejects_quantity_outside_paliers(): void
    {
        $offre = $this->createActiveOffre(3000);
        $offre->update([
            'paliers' => [
                ['min' => 2, 'max' => 4, 'prix' => 3000],
                ['min' => 5, 'max' => null, 'prix' => 2000],
            ],
        ]);

        $this->getJson("/api/v1/client/offres/{$offre->id}/estimation?quantite=1")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantite']);
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
