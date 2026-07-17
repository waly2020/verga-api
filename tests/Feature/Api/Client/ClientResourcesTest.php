<?php

namespace Tests\Feature\Api\Client;

use App\Models\Client;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    public function test_client_can_register_with_documents(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/v1/client/register', [
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'email' => 'sarah.docs@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'telephone' => '0622222222',
            'type' => 'particulier',
            'documents' => [
                [
                    'fichier' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
                    'type_document' => 'piece_identite',
                ],
                [
                    'fichier' => UploadedFile::fake()->image('passeport.jpg'),
                    'type_document' => 'passeport',
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('user.client.documents.0.type_document', 'piece_identite')
            ->assertJsonPath('user.client.documents.1.type_document', 'passeport')
            ->assertJsonStructure([
                'user' => [
                    'client' => [
                        'documents' => [
                            ['id', 'type_document', 'chemin', 'url', 'nom_original'],
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseHas('documents', [
            'type_document' => 'piece_identite',
            'documentable_type' => 'client',
        ]);
    }

    public function test_client_me_includes_documents(): void
    {
        Storage::fake('public');
        ['token' => $token, 'client' => $client] = $this->createAuthenticatedClient();

        $client->documents()->create([
            'type_document' => 'piece_identite',
            'chemin' => "documents/clients/{$client->id}/cni.pdf",
            'nom_original' => 'cni.pdf',
        ]);

        $this->withClientToken($token)
            ->getJson('/api/v1/client/me')
            ->assertOk()
            ->assertJsonPath('data.client.documents.0.type_document', 'piece_identite')
            ->assertJsonStructure([
                'data' => [
                    'client' => [
                        'documents' => [
                            ['id', 'type_document', 'chemin', 'url', 'nom_original'],
                        ],
                    ],
                ],
            ]);
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

        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
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
            'code' => 'PAY-CLIENT-001',
            'montant_sous_total' => 17500,
            'montant' => 17500,
            'methode' => 'mobile_money',
            'operateur' => 'airtel_money',
            'reference' => 'PAY-CLIENT-001',
            'bamboo_reference' => 'TXN-CLIENT-001',
            'statut' => 'validé',
        ]);

        $this->withClientToken($token)
            ->getJson('/api/v1/client/commandes')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'CMD-CLIENT-001')
            ->assertJsonPath('data.0.offre.capacite_totale', 1000)
            ->assertJsonPath('data.0.offre.capacite_disponible', 1000);
        $this->withClientToken($token)->getJson('/api/v1/client/colis')->assertOk()->assertJsonPath('data.0.reference', 'COL-CLIENT-001');
        $this->withClientToken($token)
            ->getJson('/api/v1/client/paiements')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'PAY-CLIENT-001')
            ->assertJsonPath('data.0.montant', 17500)
            ->assertJsonPath('data.0.statut', 'validé')
            ->assertJsonPath('data.0.operateur', 'airtel_money')
            ->assertJsonPath('data.0.bamboo_reference', 'TXN-CLIENT-001')
            ->assertJsonPath('data.0.commande_code', 'CMD-CLIENT-001')
            ->assertJsonMissingPath('data.0.montant_commission_client')
            ->assertJsonMissingPath('data.0.montant_sous_total');
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

        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Agence',
            'email' => 'a@test.com',
            'telephone' => '0611111111',
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
