<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Agence;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\TypeOffre;
use App\Models\User;

class AgenceResourcesTest extends AgenceApiTestCase
{
    public function test_agence_can_list_and_create_offres(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre existante',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/offres')
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
                    'updated_at',
                ]],
            ]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'titre' => 'Nouvelle offre',
                'type' => 'conteneur',
                'prix' => 225000,
                'capacite_totale' => 1,
                'origine' => 'France',
                'destination' => 'Libreville',
                'description' => 'Conteneur complet',
            ])
            ->assertCreated()
            ->assertJsonPath('data.titre', 'Nouvelle offre')
            ->assertJsonPath('data.type', 'conteneur')
            ->assertJsonStructure(['data' => ['type_offre_id', 'type_offre']]);

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'titre' => 'Nouvelle offre',
            'type' => 'conteneur',
        ]);
    }

    public function test_agence_can_create_offre_with_type_offre_id(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $typeOffre = TypeOffre::query()->where('slug', 'metre_cube')->firstOrFail();

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'titre' => 'Offre m³',
                'type_offre_id' => $typeOffre->id,
                'prix' => 15000,
                'capacite_totale' => 50,
                'origine' => 'Libreville',
                'destination' => 'Paris',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'metre_cube')
            ->assertJsonPath('data.type_offre_id', $typeOffre->id);

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'titre' => 'Offre m³',
            'type' => 'metre_cube',
            'type_offre_id' => $typeOffre->id,
        ]);
    }

    public function test_agence_can_update_offre(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre initiale',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 800,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/offres/{$offre->id}", [
                'titre' => 'Offre mise à jour',
                'type' => 'particulier',
                'prix' => 9000,
                'capacite_totale' => 1200,
                'origine' => 'France',
                'destination' => 'Port-Gentil',
                'description' => 'Nouvelle description',
                'statut' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.titre', 'Offre mise à jour')
            ->assertJsonPath('data.capacite_totale', 1200)
            ->assertJsonPath('data.capacite_disponible', 1000)
            ->assertJsonPath('data.statut', 'inactive');

        $this->assertDatabaseHas('offres', [
            'id' => $offre->id,
            'titre' => 'Offre mise à jour',
            'capacite_disponible' => 1000,
        ]);
    }

    public function test_agence_cannot_reduce_capacite_below_reserved_stock(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre stock partiel',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 700,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/offres/{$offre->id}", [
                'titre' => 'Offre stock partiel',
                'type' => 'particulier',
                'prix' => 8750,
                'capacite_totale' => 200,
                'origine' => 'Chine',
                'destination' => 'Libreville',
                'statut' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacite_totale']);
    }

    public function test_agence_can_delete_offre_without_commandes(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'À supprimer',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'A',
            'destination' => 'B',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/offres/{$offre->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Offre supprimée avec succès.');

        $this->assertDatabaseMissing('offres', ['id' => $offre->id]);
    }

    public function test_agence_cannot_delete_offre_with_commandes(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $client = $this->createClient();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre liée',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'A',
            'destination' => 'B',
            'statut' => 'active',
        ]);

        Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-DEL-001',
            'quantite' => 10,
            'montant_total' => 10000,
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/offres/{$offre->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['offre']);

        $this->assertDatabaseHas('offres', ['id' => $offre->id]);
    }

    public function test_agence_cannot_access_another_agences_offre(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();

        $otherUser = User::factory()->create(['role' => 'agence']);
        $otherAgence = Agence::create([
            'user_id' => $otherUser->id,
            'nom' => 'Autre agence',
            'email' => 'autre@test.com',
            'telephone' => '0699999999',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $otherAgence->id,
            'titre' => 'Offre privée',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->getJson("/api/v1/agence/offres/{$offre->id}")
            ->assertNotFound();
    }

    public function test_agence_can_list_commandes_and_update_statut(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $client = $this->createClient();
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

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-TEST-001',
            'quantite' => 10,
            'montant_total' => 87500,
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/commandes')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'CMD-TEST-001')
            ->assertJsonPath('data.0.offre.capacite_totale', 1000)
            ->assertJsonPath('data.0.offre.capacite_disponible', 1000);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/commandes/{$commande->id}/statut", [
                'statut' => 'confirmée',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'confirmée')
            ->assertJsonPath('data.offre.capacite_totale', 1000)
            ->assertJsonPath('data.offre.capacite_disponible', 1000);
    }

    public function test_agence_can_list_colis_and_advance_statut(): void
    {
        ['agence' => $agence, 'user' => $user, 'token' => $token] = $this->createAuthenticatedAgence();

        $client = $this->createClient();
        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre colis',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-COLIS-001',
            'quantite' => 5,
            'montant_total' => 43750,
            'statut' => 'confirmée',
        ]);

        $colis = Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-001',
            'statut' => 'déposé',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/colis')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'COL-001');

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut", [
                'commentaire' => 'Expédié ce matin',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'en_transit')
            ->assertJsonPath('next_statut', 'arrivé');

        $this->assertDatabaseHas('historique_colis', [
            'colis_id' => $colis->id,
            'user_id' => $user->id,
            'statut' => 'en_transit',
        ]);
    }

    public function test_agence_can_create_and_update_reclamation_statut(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $response = $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/reclamations', [
                'nom' => 'Mba',
                'prenom' => 'Paul',
                'telephone' => '0611111111',
                'email' => 'paul@test.com',
                'objet' => 'Colis endommagé',
                'description' => 'Le colis est arrivé abîmé.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.statut', 'ouverte');

        $reclamationId = $response->json('data.id');

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/reclamations/{$reclamationId}/statut", [
                'statut' => 'en_cours',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'en_cours');

        $this->assertDatabaseHas('reclamations', [
            'id' => $reclamationId,
            'agence_id' => $agence->id,
        ]);
    }

    public function test_agence_can_list_paiements(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $client = $this->createClient();
        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre paiement',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-PAY-001',
            'quantite' => 2,
            'montant_total' => 17500,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'montant' => 17500,
            'methode' => 'mobile_money',
            'reference' => 'PAY-001',
            'statut' => 'validé',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/paiements')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'PAY-001');
    }
}
