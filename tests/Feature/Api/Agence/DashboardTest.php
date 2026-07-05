<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Commission;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\Reclamation;
use App\Models\Reversement;

class DashboardTest extends AgenceApiTestCase
{
    public function test_agence_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v1/agence/dashboard')
            ->assertUnauthorized();
    }

    public function test_agence_can_view_dashboard_stats(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $this->seedAgenceActivity($agence);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/dashboard?periode=mois')
            ->assertOk()
            ->assertJsonPath('data.profil.nom', $agence->nom)
            ->assertJsonPath('data.stats.nb_offres', 2)
            ->assertJsonPath('data.stats.nb_offres_actives', 1)
            ->assertJsonPath('data.stats.nb_commandes', 2)
            ->assertJsonPath('data.stats.nb_commandes_en_attente', 1)
            ->assertJsonPath('data.stats.total_paiements', 50000)
            ->assertJsonPath('data.stats.total_sous_total', 47500)
            ->assertJsonPath('data.stats.total_commissions_client', 2500)
            ->assertJsonPath('data.stats.total_commissions_agence', 2500)
            ->assertJsonPath('data.stats.total_commissions', 2500)
            ->assertJsonPath('data.stats.revenu_net_estime', 45000)
            ->assertJsonPath('data.stats.reversements_en_attente', 10000)
            ->assertJsonStructure([
                'data' => [
                    'periode',
                    'debut',
                    'fin',
                    'profil' => ['nom', 'ville', 'statut'],
                    'stats' => [
                        'nb_offres',
                        'nb_offres_actives',
                        'capacite_disponible_totale',
                        'nb_commandes',
                        'nb_commandes_en_attente',
                        'nb_commandes_confirmees',
                        'total_paiements',
                        'total_sous_total',
                        'total_commissions_client',
                        'total_commissions_agence',
                        'total_commissions',
                        'revenu_net_estime',
                        'reversements_en_attente',
                        'nb_colis',
                        'nb_colis_en_transit',
                        'nb_reclamations_ouvertes',
                    ],
                    'commandes_par_statut',
                    'colis_par_statut',
                    'top_offres',
                    'dernieres_commandes',
                ],
            ]);
    }

    public function test_agence_dashboard_is_isolated_between_agences(): void
    {
        ['agence' => $agenceA, 'token' => $tokenA] = $this->createAuthenticatedAgence();
        ['agence' => $agenceB] = $this->createAuthenticatedAgence([
            'email' => 'autre-agence@test.com',
            'nom' => 'Autre Agence',
        ]);

        $this->seedAgenceActivity($agenceB);

        $this->withAgenceToken($tokenA)
            ->getJson('/api/v1/agence/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.nb_commandes', 0);
    }

    private function seedAgenceActivity(Agence $agence): void
    {
        $client = $this->createClient();

        $offreActive = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre active',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 800,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre inactive',
            'type' => 'conteneur',
            'prix' => 500000,
            'capacite_totale' => 5,
            'capacite_disponible' => 5,
            'origine' => 'France',
            'destination' => 'Port-Gentil',
            'statut' => 'inactive',
        ]);

        $commandeConfirmee = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offreActive->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-AG-DASH-001',
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'quantite' => 2,
            'montant_total' => 50000,
            'statut' => 'confirmée',
        ]);

        $commandeAttente = Commande::create([
            'client_id' => null,
            'offre_id' => $offreActive->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-AG-DASH-002',
            'nom' => 'Invité',
            'prenom' => 'Test',
            'telephone' => '0600000001',
            'quantite' => 1,
            'montant_total' => 8750,
            'statut' => 'en_attente',
        ]);

        Paiement::create([
            'commande_id' => $commandeConfirmee->id,
            'code' => 'PAY-AG-DASH-001',
            'montant' => 50000,
            'montant_sous_total' => 47500,
            'montant_commission_client' => 2500,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        Commission::create([
            'commande_id' => $commandeConfirmee->id,
            'montant' => 2500,
            'taux' => 5,
        ]);

        Colis::create([
            'commande_id' => $commandeConfirmee->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-AG-DASH-001',
            'statut' => 'en_transit',
        ]);

        Colis::create([
            'commande_id' => $commandeAttente->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-AG-DASH-002',
            'statut' => 'déposé',
        ]);

        Reclamation::create([
            'client_id' => $client->id,
            'commande_id' => $commandeConfirmee->id,
            'agence_id' => $agence->id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'email' => $client->email,
            'objet' => 'Problème livraison',
            'description' => 'Détail',
            'statut' => 'ouverte',
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 10000,
            'periode' => '2026-06',
            'statut' => 'en_attente',
        ]);
    }
}
