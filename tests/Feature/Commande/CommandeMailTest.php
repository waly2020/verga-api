<?php

namespace Tests\Feature\Commande;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use App\Mail\Commande\CommandeCreatedAdminMail;
use App\Mail\Commande\CommandeCreatedAgenceMail;
use App\Mail\Commande\CommandeCreatedClientMail;
use App\Mail\Commande\PaiementCommandeEchecClientMail;
use App\Mail\Commande\PaiementCommandeValideAgenceMail;
use App\Mail\Commande\PaiementCommandeValideClientMail;
use App\Models\Agence;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\User;
use App\Services\BambooPayService;
use App\Services\CommandeCheckoutService;
use App\Services\PaymentSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Feature\Api\Client\ClientApiTestCase;

class CommandeMailTest extends ClientApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bamboopay.base_url' => 'https://devfront-bamboopay.ventis.group',
            'bamboopay.merchant_id' => 'merchant-test',
            'bamboopay.username' => 'merchant-user',
            'bamboopay.password' => 'merchant-pass',
            'bamboopay.callback_url' => 'https://verga.test/api/v1/payments/bamboo-pay/callback',
            'verga.mail.admin_address' => 'admin@verga.test',
        ]);
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

    public function test_checkout_queues_creation_mails_for_client_agence_and_admin(): void
    {
        Mail::fake();
        $this->mockBambooRedirect();

        ['agence' => $agence, 'offre' => $offre] = $this->createCheckoutOffre();

        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'client-commande@example.com',
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'email' => 'client-commande@example.com',
            'telephone' => '0622222222',
            'statut' => 'actif',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/client/commandes', [
                'offre_id' => $offre->id,
                'nom' => $client->nom,
                'prenom' => $client->prenom,
                'telephone' => $client->telephone,
                'quantite' => 2,
            ])
            ->assertCreated();

        Mail::assertQueued(CommandeCreatedClientMail::class, fn ($mail) => $mail->hasTo('client-commande@example.com'));
        Mail::assertQueued(CommandeCreatedAgenceMail::class, fn ($mail) => $mail->hasTo($agence->email));
        Mail::assertQueued(CommandeCreatedAdminMail::class, fn ($mail) => $mail->hasTo('admin@verga.test'));
    }

    public function test_payment_completed_queues_validation_mails(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createCheckoutOffre();

        ['client' => $client] = $this->createAuthenticatedClient([
            'email' => 'client-paiement@example.com',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $agence->offres()->first()->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-MAIL-001',
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0622222222',
            'quantite' => 2,
            'quantite_payee' => 0,
            'montant_sous_total' => 0,
            'montant_commission_client' => 0,
            'montant_total' => 0,
            'capacite_bloquee' => false,
            'statut' => 'en_attente',
        ]);

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-MAIL-001',
            'quantite' => 2,
            'montant_sous_total' => 5000,
            'montant_commission_client' => 0,
            'montant' => 5000,
            'methode' => 'bamboo_redirect',
            'statut' => 'en_attente',
        ]);

        app(PaymentSettlementService::class)->settleFromBambooStatus($paiement, 'completed');

        Mail::assertQueued(PaiementCommandeValideClientMail::class, fn ($mail) => $mail->hasTo('client-paiement@example.com'));
        Mail::assertQueued(PaiementCommandeValideAgenceMail::class, fn ($mail) => $mail->hasTo($agence->email));
    }

    public function test_payment_failed_queues_client_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createCheckoutOffre();

        ['client' => $client] = $this->createAuthenticatedClient([
            'email' => 'client-echec@example.com',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $agence->offres()->first()->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-MAIL-002',
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0622222222',
            'quantite' => 2,
            'quantite_payee' => 0,
            'montant_sous_total' => 0,
            'montant_commission_client' => 0,
            'montant_total' => 0,
            'capacite_bloquee' => false,
            'statut' => 'en_attente',
        ]);

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-MAIL-002',
            'quantite' => 2,
            'montant_sous_total' => 5000,
            'montant_commission_client' => 0,
            'montant' => 5000,
            'methode' => 'bamboo_redirect',
            'statut' => 'en_attente',
        ]);

        app(PaymentSettlementService::class)->settleFromBambooStatus($paiement, 'failed', 'Solde insuffisant');

        Mail::assertQueued(PaiementCommandeEchecClientMail::class, fn ($mail) => $mail->hasTo('client-echec@example.com'));
    }

    public function test_guest_with_email_receives_mail_only_on_final_payment_status(): void
    {
        Mail::fake();
        $this->mockBambooRedirect();

        ['agence' => $agence, 'offre' => $offre] = $this->createCheckoutOffre();

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'nom' => 'Mbadinga',
            'prenom' => 'Jean',
            'telephone' => '0622222222',
            'email' => 'invite@example.com',
            'quantite' => 2,
        ])->assertCreated();

        $this->assertDatabaseHas('commandes', [
            'code' => $create->json('code'),
            'email' => 'invite@example.com',
            'client_id' => null,
        ]);

        Mail::assertNotQueued(CommandeCreatedClientMail::class);
        Mail::assertQueued(CommandeCreatedAgenceMail::class, fn ($mail) => $mail->hasTo($agence->email));
        Mail::assertQueued(CommandeCreatedAdminMail::class);

        $paiementCode = $create->json('paiement_code');

        app(PaymentSettlementService::class)->settleFromCallback([
            'reference' => $paiementCode,
            'billingId' => 'TXN-GUEST-001',
            'status' => 'completed',
        ]);

        Mail::assertQueued(PaiementCommandeValideClientMail::class, fn ($mail) => $mail->hasTo('invite@example.com'));
    }

    public function test_guest_without_email_does_not_receive_client_mails(): void
    {
        Mail::fake();
        $this->mockBambooRedirect();

        ['offre' => $offre] = $this->createCheckoutOffre();

        $create = $this->postJson('/api/v1/client/commandes', [
            'offre_id' => $offre->id,
            'nom' => 'Mbadinga',
            'prenom' => 'Jean',
            'telephone' => '0622222222',
            'quantite' => 2,
        ])->assertCreated();

        app(PaymentSettlementService::class)->settleFromCallback([
            'reference' => $create->json('paiement_code'),
            'billingId' => 'TXN-GUEST-002',
            'status' => 'failed',
            'description' => 'Solde insuffisant',
        ]);

        Mail::assertNotQueued(CommandeCreatedClientMail::class);
        Mail::assertNotQueued(PaiementCommandeValideClientMail::class);
        Mail::assertNotQueued(PaiementCommandeEchecClientMail::class);
    }

    /**
     * @return array{agence: Agence, offre: Offre}
     */
    private function createCheckoutOffre(): array
    {
        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit Mail',
            'email' => 'agence-mail@test.com',
            'telephone' => '0611111111',
        ]);

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Groupage Paris',
            'type' => 'particulier',
            'prix' => 2500,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'Libreville',
            'arrivee' => 'Paris',
            'statut' => 'active',
        ]);

        return compact('agence', 'offre');
    }
}
