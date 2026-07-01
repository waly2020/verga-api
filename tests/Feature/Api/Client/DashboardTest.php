<?php

namespace Tests\Feature\Api\Client;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\Reclamation;
use App\Models\User;

class DashboardTest extends ClientApiTestCase
{
    public function test_client_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v1/client/dashboard')
            ->assertUnauthorized();
    }

    public function test_entreprise_client_can_view_dashboard_stats(): void
    {
        ['client' => $client, 'token' => $token] = $this->createAuthenticatedClient([
            'type' => 'entreprise',
            'nom' => 'SARL Import',
            'prenom' => 'Gabon',
        ]);

        $this->seedClientActivity($client);

        $this->withClientToken($token)
            ->getJson('/api/v1/client/dashboard?periode=mois')
            ->assertOk()
            ->assertJsonPath('data.profil.type', 'entreprise')
            ->assertJsonPath('data.stats.nb_commandes', 2)
            ->assertJsonPath('data.stats.nb_commandes_en_attente', 1)
            ->assertJsonPath('data.stats.nb_colis_en_transit', 1)
            ->assertJsonPath('data.stats.total_depense', 50000)
            ->assertJsonPath('data.stats.nb_reclamations_ouvertes', 1)
            ->assertJsonStructure([
                'data' => [
                    'periode',
                    'debut',
                    'fin',
                    'profil' => ['type', 'nom', 'prenom'],
                    'stats' => [
                        'nb_commandes',
                        'nb_commandes_en_attente',
                        'nb_commandes_confirmees',
                        'nb_colis',
                        'nb_colis_en_transit',
                        'nb_colis_arrives',
                        'total_depense',
                        'nb_reclamations',
                        'nb_reclamations_ouvertes',
                    ],
                    'commandes_par_statut',
                    'colis_par_statut',
                    'dernieres_commandes',
                ],
            ]);
    }

    public function test_client_cannot_see_other_client_dashboard_data(): void
    {
        ['client' => $clientA, 'token' => $tokenA] = $this->createAuthenticatedClient();
        ['client' => $clientB] = $this->createAuthenticatedClient([
            'email' => 'autre@example.com',
        ]);

        $this->seedClientActivity($clientB);

        $this->withClientToken($tokenA)
            ->getJson('/api/v1/client/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.nb_commandes', 0)
            ->assertJsonPath('data.stats.total_depense', 0);

        $this->seedClientActivity($clientA, '-A');

        $this->withClientToken($tokenA)
            ->getJson('/api/v1/client/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.nb_commandes', 2);
    }

    public function test_client_dashboard_validates_periode(): void
    {
        ['token' => $token] = $this->createAuthenticatedClient();

        $this->withClientToken($token)
            ->getJson('/api/v1/client/dashboard?periode=invalide')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['periode']);
    }

    private function seedClientActivity(Client $client, string $suffix = ''): void
    {
        $agenceUser = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $agenceUser->id,
            'nom' => 'Transit Test',
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre test',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commandeConfirmee = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-DASH-001'.$suffix,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'quantite' => 2,
            'montant_total' => 50000,
            'statut' => 'confirmée',
        ]);

        $commandeAttente = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-DASH-002'.$suffix,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'quantite' => 1,
            'montant_total' => 8750,
            'statut' => 'en_attente',
        ]);

        Paiement::create([
            'commande_id' => $commandeConfirmee->id,
            'code' => 'PAY-DASH-001'.$suffix,
            'montant' => 50000,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        Colis::create([
            'commande_id' => $commandeConfirmee->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-DASH-001'.$suffix,
            'description' => 'Cartons',
            'statut' => 'en_transit',
        ]);

        Colis::create([
            'commande_id' => $commandeAttente->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-DASH-002'.$suffix,
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
            'objet' => 'Retard',
            'description' => 'Colis en retard',
            'statut' => 'ouverte',
        ]);
    }
}
