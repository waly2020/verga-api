<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\Reversement;

class SoldeTest extends AgenceApiTestCase
{
    public function test_solde_requires_authentication(): void
    {
        $this->getJson('/api/v1/agence/solde')
            ->assertUnauthorized();
    }

    public function test_agence_can_view_its_solde(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre solde',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-SOLDE-001',
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 1,
            'montant_total' => 50000,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-SOLDE-001',
            'montant' => 50000,
            'montant_sous_total' => 47500,
            'montant_commission_client' => 2500,
            'montant_commission_agence' => 2375,
            'montant_agence' => 45125,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 10000,
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
            ->getJson('/api/v1/agence/solde')
            ->assertOk()
            ->assertJsonPath('data.montant_paiements_valides', 45125)
            ->assertJsonPath('data.montant_reversements', 10000)
            ->assertJsonPath('data.montant_solde', 35125)
            ->assertJsonPath('data.montant_reversements_en_attente', 5000)
            ->assertJsonPath('data.montant_disponible', 30125);
    }

    public function test_solde_is_isolated_between_agences(): void
    {
        ['token' => $tokenA] = $this->createAuthenticatedAgence();
        ['agence' => $agenceB] = $this->createAuthenticatedAgence([
            'email' => 'autre-agence@test.com',
            'nom' => 'Autre Agence',
        ]);

        $offre = Offre::create([
            'agence_id' => $agenceB->id,
            'titre' => 'Offre B',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agenceB->id,
            'code' => 'CMD-SOLDE-B-001',
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000001',
            'quantite' => 1,
            'montant_total' => 25000,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-SOLDE-B-001',
            'montant' => 25000,
            'montant_sous_total' => 23750,
            'montant_commission_client' => 1250,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        $this->withAgenceToken($tokenA)
            ->getJson('/api/v1/agence/solde')
            ->assertOk()
            ->assertJsonPath('data.montant_paiements_valides', 0)
            ->assertJsonPath('data.montant_solde', 0);
    }
}
