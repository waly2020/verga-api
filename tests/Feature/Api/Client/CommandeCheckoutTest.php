<?php

namespace Tests\Feature\Api\Client;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\CheckStatusRequest;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use App\Models\Agence;
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
            'bamboopay.return_url' => 'https://verga.test/paiement/retour',
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
        $this->assertDatabaseHas('colis', ['description' => 'Vêtements']);
        $this->assertDatabaseHas('paiements', ['statut' => 'en_attente', 'montant' => 25000]);

        $offre->refresh();
        $this->assertEquals(1000, (float) $offre->capacite_disponible);
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
        ])->assertOk();

        $offre->refresh();
        $this->assertEquals(100, (float) $offre->capacite_disponible);
        $this->assertDatabaseHas('commandes', ['statut' => 'annulée']);
        $this->assertDatabaseHas('paiements', ['statut' => 'échec']);
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
            ->assertJsonPath('data.0.capacite_disponible', '1000.000');
    }
}
