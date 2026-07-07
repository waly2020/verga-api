<?php

namespace Tests\Feature\Api\Client;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\CheckStatusRequest;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use App\Models\Agence;
use App\Models\ConfigurationCommission;
use App\Models\Offre;
use App\Models\User;
use App\Services\BambooPayService;
use App\Services\CommandeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class CommandeCheckoutTest extends ClientApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        config([
            'bamboopay.base_url' => 'https://devfront-bamboopay.ventis.group',
            'bamboopay.merchant_id' => 'merchant-test',
            'bamboopay.username' => 'merchant-user',
            'bamboopay.password' => 'merchant-pass',
            'bamboopay.callback_url' => 'https://verga.test/api/v1/payments/bamboo-pay/callback',
        ]);
    }

    /**
     * @return array{agence: Agence, offre: Offre}
     */
    private function createActiveOffre(float $capacite = 1000): array
    {
        $user = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $user->id,
            'nom' => 'Transit Test',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Groupage Paris',
            'type' => 'particulier',
            'prix' => 2500,
            'capacite_totale' => $capacite,
            'capacite_disponible' => $capacite,
            'origine' => 'Libreville',
            'destination' => 'Paris',
            'statut' => 'active',
        ]);

        return compact('agence', 'offre');
    }

    private function mockBambooRedirect(): void
    {
        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            RedirectPaymentRequest::class => MockResponse::make([
                'redirect_url' => 'https://bamboo.test/pay/abc',
            ], 200),
        ]));

        $this->app->forgetInstance(BambooPayService::class);
        $this->app->forgetInstance(CommandeCheckoutService::class);
        $this->app->instance(BambooPayConnector::class, $connector);
    }

    public function test_guest_can_create_commande_and_receive_payment_links(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre();

        $response = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 10,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0612345678',
            'description' => 'Vêtements',
        ]);

        $response->assertCreated()
            ->assertJsonPath('montant_total', 25000)
            ->assertJsonPath('redirect_url', 'https://bamboo.test/pay/abc')
            ->assertJsonStructure(['code', 'paiement_code', 'verification_url']);

        $this->assertDatabaseHas('commandes', [
            'offre_id' => $offre->id,
            'client_id' => null,
            'nom' => 'Obame',
            'statut' => 'en_attente',
        ]);
        $this->assertDatabaseHas('colis', ['description' => 'Vêtements', 'statut' => 'chez_client']);
        $this->assertDatabaseHas('paiements', ['statut' => 'en_attente', 'montant' => 25000]);

        $offre->refresh();
        $this->assertEquals(1000, (float) $offre->capacite_disponible);
    }

    public function test_reservation_checkout_applies_commission_on_paid_quantity_only(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);

        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 5,
            'actif' => true,
        ]);

        $response = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 30,
            'quantite_reservee' => 50,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0612345678',
        ]);

        $response->assertCreated()
            ->assertJsonPath('montant_sous_total', 75000)
            ->assertJsonPath('montant_commission_client', 3750)
            ->assertJsonPath('montant_total', 78750)
            ->assertJsonPath('quantite_reservee', 50)
            ->assertJsonPath('quantite_a_payer', 30)
            ->assertJsonPath('mode', 'reservation');

        $this->assertDatabaseHas('commandes', [
            'quantite' => 50,
            'quantite_payee' => 0,
            'montant_sous_total' => 0,
            'montant_commission_client' => 0,
            'montant_total' => 0,
            'statut' => 'en_attente',
        ]);
        $this->assertDatabaseHas('paiements', [
            'statut' => 'en_attente',
            'quantite' => 30,
            'montant' => 78750,
            'montant_commission_client' => 3750,
        ]);
    }

    public function test_partial_payment_sets_reserved_status_and_blocks_full_capacity(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 30,
            'quantite_reservee' => 50,
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0612345678',
        ])->assertCreated();

        $commandeId = $create->json('commande_id');
        $paiementCode = $create->json('paiement_code');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $paiementCode,
            'reference' => 'TXN-PARTIAL-001',
            'status' => 'completed',
        ])->assertOk();

        $offre->refresh();
        $this->assertEquals(50, (float) $offre->capacite_disponible);
        $this->assertDatabaseHas('commandes', [
            'id' => $commandeId,
            'statut' => 'réservée',
            'quantite_payee' => 30,
            'capacite_bloquee' => true,
        ]);
    }

    public function test_balance_payment_completes_reservation_with_commission_on_each_payment(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);

        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 10,
            'actif' => true,
        ]);

        ['client' => $client, 'token' => $token] = $this->createAuthenticatedClient();

        $create = $this->withClientToken($token)
            ->postJson('/api/v1/client/commandes', [
                'offre_id' => $offre->id,
                'quantite' => 30,
                'quantite_reservee' => 50,
                'telephone' => '0612345678',
            ])
            ->assertCreated();

        $commandeId = $create->json('commande_id');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $create->json('paiement_code'),
            'reference' => 'TXN-PARTIAL-002',
            'status' => 'completed',
        ]);

        $balance = $this->withClientToken($token)
            ->postJson("/api/v1/client/commandes/{$commandeId}/paiements", [
                'quantite' => 20,
            ])
            ->assertCreated()
            ->assertJsonPath('quantite_a_payer', 20)
            ->assertJsonPath('montant_sous_total', 50000)
            ->assertJsonPath('montant_commission_client', 5000)
            ->assertJsonPath('montant_total', 55000)
            ->assertJsonPath('mode', 'complet');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $balance->json('paiement_code'),
            'reference' => 'TXN-BALANCE-001',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id' => $commandeId,
            'statut' => 'confirmée',
            'quantite_payee' => 50,
            'montant_sous_total' => 125000,
            'montant_commission_client' => 12500,
            'montant_total' => 137500,
        ]);
        $this->assertDatabaseCount('paiements', 2);
    }

    public function test_failed_balance_payment_keeps_reserved_status(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);
        ['token' => $token] = $this->createAuthenticatedClient();

        $create = $this->withClientToken($token)
            ->postJson('/api/v1/client/commandes', [
                'offre_id' => $offre->id,
                'quantite' => 30,
                'quantite_reservee' => 50,
                'telephone' => '0612345678',
            ])
            ->assertCreated();

        $commandeId = $create->json('commande_id');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $create->json('paiement_code'),
            'status' => 'completed',
        ]);

        $balance = $this->withClientToken($token)
            ->postJson("/api/v1/client/commandes/{$commandeId}/paiements", ['quantite' => 20])
            ->assertCreated();

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $balance->json('paiement_code'),
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id' => $commandeId,
            'statut' => 'réservée',
            'quantite_payee' => 30,
        ]);
    }

    public function test_checkout_includes_client_commission_in_total_sent_to_bamboo(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre();

        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 5,
            'actif' => true,
        ]);

        $response = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 10,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0612345678',
        ]);

        $response->assertCreated()
            ->assertJsonPath('montant_sous_total', 25000)
            ->assertJsonPath('montant_commission_client', 1250)
            ->assertJsonPath('montant_total', 26250);

        $this->assertDatabaseHas('commandes', [
            'montant_sous_total' => 0,
            'montant_commission_client' => 0,
            'montant_total' => 0,
        ]);
        $this->assertDatabaseHas('paiements', [
            'statut' => 'en_attente',
            'montant' => 26250,
            'montant_commission_client' => 1250,
        ]);
    }

    public function test_authenticated_client_order_is_linked_to_account(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre();
        ['client' => $client, 'token' => $token] = $this->createAuthenticatedClient();

        $this->withClientToken($token)
            ->postJson('/api/v1/client/commandes', [
                'offre_id' => $offre->id,
                'quantite' => 5,
                'telephone' => '0699999999',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('commandes', [
            'client_id' => $client->id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
        ]);
    }

    public function test_callback_completed_updates_order_and_decrements_offer(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 10,
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0612345678',
        ])->assertCreated();

        $paiementCode = $create->json('paiement_code');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $paiementCode,
            'reference' => 'TXN-2025-001',
            'status' => 'completed',
        ])->assertOk();

        $offre->refresh();
        $this->assertEquals(90, (float) $offre->capacite_disponible);
        $this->assertDatabaseHas('paiements', ['code' => $paiementCode, 'statut' => 'validé']);
        $this->assertDatabaseHas('commandes', ['statut' => 'confirmée']);
    }

    public function test_payment_status_returns_financial_breakdown_after_validation(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);

        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 5,
            'actif' => true,
        ]);

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 10,
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0612345678',
        ])->assertCreated();

        $paiementCode = $create->json('paiement_code');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $paiementCode,
            'reference' => 'TXN-FIN-001',
            'status' => 'completed',
        ])->assertOk();

        $this->getJson("/api/v1/client/paiements/{$paiementCode}/statut")
            ->assertOk()
            ->assertJsonPath('statut', 'validé')
            ->assertJsonPath('quantite', 10)
            ->assertJsonPath('montant_sous_total', 25000)
            ->assertJsonPath('montant_commission_client', 1250)
            ->assertJsonPath('montant_total', 26250)
            ->assertJsonPath('commande_montant_sous_total', 25000)
            ->assertJsonPath('commande_montant_commission_client', 1250)
            ->assertJsonPath('commande_montant_total', 26250);
    }

    public function test_callback_failed_does_not_decrement_offer(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(100);

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 10,
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0612345678',
        ])->assertCreated();

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $create->json('paiement_code'),
            'status' => 'failed',
            'observation' => 'Paiement annulé par le client',
        ])->assertOk();

        $offre->refresh();
        $this->assertEquals(100, (float) $offre->capacite_disponible);
        $this->assertDatabaseHas('commandes', ['statut' => 'annulée']);
        $this->assertDatabaseHas('paiements', [
            'statut' => 'échec',
            'bamboo_message' => 'Paiement annulé par le client',
        ]);
    }

    public function test_verify_status_is_idempotent_with_callback(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre(50);

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 10,
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0612345678',
        ])->assertCreated();

        $paiementCode = $create->json('paiement_code');

        $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'billingId' => $paiementCode,
            'reference' => 'TXN-2025-002',
            'status' => 'completed',
        ]);

        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            CheckStatusRequest::class => MockResponse::make([
                'transaction' => ['status' => 'completed'],
            ], 200),
        ]));
        $this->app->forgetInstance(BambooPayService::class);
        $this->app->forgetInstance(CommandeCheckoutService::class);
        $this->app->instance(BambooPayConnector::class, $connector);

        $this->getJson("/api/v1/client/paiements/{$paiementCode}/statut")
            ->assertOk()
            ->assertJsonPath('statut', 'validé');

        $offre->refresh();
        $this->assertEquals(40, (float) $offre->capacite_disponible);
    }

    public function test_guest_checkout_can_attach_photos(): void
    {
        $this->mockBambooRedirect();
        ['offre' => $offre] = $this->createActiveOffre();

        $this->post('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'quantite' => 2,
            'nom' => 'Photo',
            'prenom' => 'Test',
            'telephone' => '0612345678',
            'photos' => [
                UploadedFile::fake()->image('colis.jpg'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertDatabaseCount('colis_photos', 1);
    }

    public function test_client_can_list_active_offres(): void
    {
        ['offre' => $offre] = $this->createActiveOffre();

        $this->getJson('/api/v1/client/offres')
            ->assertOk()
            ->assertJsonPath('data.0.id', $offre->id)
            ->assertJsonPath('data.0.capacite_disponible', 1000)
            ->assertJsonPath('data.0.capacite_totale', 1000);
    }
}
