<?php

namespace Tests\Feature\Admin;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\CheckStatusRequest;
use App\Models\Agence;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\User;
use App\Services\BambooPayService;
use App\Services\CommandeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

class PaiementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{commande: Commande, paiement: Paiement}
     */
    private function createPendingPayment(?string $bambooReference = null): array
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
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Libreville',
            'destination' => 'Paris',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-TEST-001',
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0612345678',
            'quantite' => 10,
            'quantite_payee' => 0,
            'montant_sous_total' => 0,
            'montant_commission_client' => 0,
            'montant_total' => 0,
            'capacite_bloquee' => false,
            'statut' => 'en_attente',
        ]);

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-TEST-001',
            'quantite' => 10,
            'montant_sous_total' => 25000,
            'montant_commission_client' => 0,
            'montant' => 25000,
            'methode' => 'bamboo_redirect',
            'bamboo_reference' => $bambooReference,
            'statut' => 'en_attente',
        ]);

        return compact('commande', 'paiement');
    }

    public function test_admin_can_verify_payment_status_with_bamboo_reference(): void
    {
        ['paiement' => $paiement] = $this->createPendingPayment('TXN-BP-001');

        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            CheckStatusRequest::class => MockResponse::make([
                'transaction' => ['status' => 'completed'],
            ], 200),
        ]));

        $this->app->forgetInstance(BambooPayService::class);
        $this->app->forgetInstance(CommandeCheckoutService::class);
        $this->app->instance(BambooPayConnector::class, $connector);

        $response = $this->actingAs($this->adminUser())
            ->patch("/admin/paiements/{$paiement->id}/verifier-statut");

        $response->assertRedirect()
            ->assertSessionHas('success', 'Paiement validé avec succès.');

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'validé',
        ]);

        $connector->getMockClient()?->assertSent(function (CheckStatusRequest $request) {
            return $request->resolveEndpoint() === '/api/check-status/TXN-BP-001';
        });
    }

    public function test_admin_can_verify_payment_status_with_verga_code_when_bamboo_reference_missing(): void
    {
        ['paiement' => $paiement] = $this->createPendingPayment();

        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            CheckStatusRequest::class => MockResponse::make([
                'transaction' => ['status' => 'pending'],
            ], 200),
        ]));

        $this->app->forgetInstance(BambooPayService::class);
        $this->app->forgetInstance(CommandeCheckoutService::class);
        $this->app->instance(BambooPayConnector::class, $connector);

        $response = $this->actingAs($this->adminUser())
            ->patch("/admin/paiements/{$paiement->id}/verifier-statut");

        $response->assertRedirect()
            ->assertSessionHas('success', 'Statut vérifié : le paiement est toujours en attente.');

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'en_attente',
        ]);

        $connector->getMockClient()?->assertSent(function (CheckStatusRequest $request) {
            return $request->resolveEndpoint() === '/api/check-status/PAY-TEST-001';
        });
    }

    public function test_admin_verify_stores_bamboo_message_on_failure(): void
    {
        ['paiement' => $paiement] = $this->createPendingPayment('TXN-BP-FAIL');

        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            CheckStatusRequest::class => MockResponse::make([
                'transaction' => [
                    'status' => 'failed',
                    'message' => 'Fonds insuffisants',
                ],
            ], 200),
        ]));

        $this->app->forgetInstance(BambooPayService::class);
        $this->app->forgetInstance(CommandeCheckoutService::class);
        $this->app->instance(BambooPayConnector::class, $connector);

        $this->actingAs($this->adminUser())
            ->patch("/admin/paiements/{$paiement->id}/verifier-statut")
            ->assertRedirect()
            ->assertSessionHas('error', 'Le paiement a échoué : Fonds insuffisants');

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'échec',
            'bamboo_message' => 'Fonds insuffisants',
        ]);
    }
}
