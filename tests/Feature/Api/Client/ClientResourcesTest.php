<?php

namespace Tests\Feature\Api\Client;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\User;

class ClientResourcesTest extends ClientApiTestCase
{
    public function test_client_can_register(): void
    {
        $response = $this->postJson('/api/v1/client/register', [
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'telephone' => '0622222222',
            'type' => 'particulier',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['client' => ['id', 'nom', 'prenom']]]);

        $this->assertDatabaseHas('clients', ['email' => 'sarah@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'sarah@example.com', 'role' => 'client']);
    }

    public function test_client_can_update_profile(): void
    {
        ['token' => $token, 'client' => $client] = $this->createAuthenticatedClient();

        $this->withClientToken($token)
            ->putJson('/api/v1/client/profile', [
                'ville' => 'Libreville',
            ])
            ->assertOk()
            ->assertJsonPath('data.client.ville', 'Libreville');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'ville' => 'Libreville',
        ]);
    }

    public function test_client_can_list_own_commandes_colis_and_paiements(): void
    {
        ['client' => $client, 'token' => $token] = $this->createAuthenticatedClient();

        $agenceUser = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $agenceUser->id,
            'nom' => 'Transit',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre',
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
            'code' => 'CMD-CLIENT-001',
            'quantite' => 2,
            'montant_total' => 17500,
            'statut' => 'confirmée',
        ]);

        Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-CLIENT-001',
            'statut' => 'déposé',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'montant' => 17500,
            'methode' => 'mobile_money',
            'reference' => 'PAY-CLIENT-001',
            'statut' => 'validé',
        ]);

        $this->withClientToken($token)->getJson('/api/v1/client/commandes')->assertOk()->assertJsonPath('data.0.code', 'CMD-CLIENT-001');
        $this->withClientToken($token)->getJson('/api/v1/client/colis')->assertOk()->assertJsonPath('data.0.reference', 'COL-CLIENT-001');
        $this->withClientToken($token)->getJson('/api/v1/client/paiements')->assertOk()->assertJsonPath('data.0.reference', 'PAY-CLIENT-001');
    }

    public function test_client_can_create_reclamation(): void
    {
        ['client' => $client, 'token' => $token] = $this->createAuthenticatedClient();

        $response = $this->withClientToken($token)->postJson('/api/v1/client/reclamations', [
            'objet' => 'Retard livraison',
            'description' => 'Mon colis est en retard.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.objet', 'Retard livraison');

        $this->assertDatabaseHas('reclamations', [
            'client_id' => $client->id,
            'objet' => 'Retard livraison',
        ]);
    }

    public function test_client_cannot_access_another_clients_commande(): void
    {
        ['token' => $token] = $this->createAuthenticatedClient();

        $other = Client::create([
            'user_id' => User::factory()->create(['role' => 'client'])->id,
            'nom' => 'Autre',
            'prenom' => 'Client',
            'email' => 'autre@test.com',
            'telephone' => '0699999999',
        ]);

        $agenceUser = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $agenceUser->id,
            'nom' => 'Agence',
            'email' => 'a@test.com',
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'O',
            'type' => 'particulier',
            'prix' => 100,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'X',
            'destination' => 'Y',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $other->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-PRIVATE',
            'quantite' => 1,
            'montant_total' => 100,
            'statut' => 'en_attente',
        ]);

        $this->withClientToken($token)
            ->getJson("/api/v1/client/commandes/{$commande->id}")
            ->assertNotFound();
    }
}
