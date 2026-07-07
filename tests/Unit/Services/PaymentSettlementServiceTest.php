<?php

namespace Tests\Unit\Services;

use App\Models\Agence;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\User;
use App\Services\PaymentSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingPayment(): Paiement
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
            'code' => 'CMD-MSG-001',
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0612345678',
            'quantite' => 10,
            'quantite_payee' => 0,
            'montant_sous_total' => 0,
            'montant_commission_client' => 0,
            'montant_total' => 0,
            'capacite_bloquee' => false,
            'statut' => 'en_attente',
        ]);

        return Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-MSG-001',
            'quantite' => 10,
            'montant_sous_total' => 25000,
            'montant_commission_client' => 0,
            'montant' => 25000,
            'methode' => 'bamboo_redirect',
            'statut' => 'en_attente',
        ]);
    }

    public function test_callback_stores_observation_on_failure(): void
    {
        $paiement = $this->createPendingPayment();

        $service = app(PaymentSettlementService::class);
        $result = $service->settleFromCallback([
            'billingId' => $paiement->code,
            'status' => 'failed',
            'observation' => 'Solde insuffisant sur le compte mobile money',
        ]);

        $this->assertNotNull($result);
        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'échec',
            'bamboo_message' => 'Solde insuffisant sur le compte mobile money',
        ]);
    }

    public function test_check_status_stores_transaction_message_on_failure(): void
    {
        $paiement = $this->createPendingPayment();

        $service = app(PaymentSettlementService::class);
        $service->settleFromBambooStatus(
            $paiement,
            'failed',
            PaymentSettlementService::messageFromCheckStatusResponse([
                'message' => 'OK',
                'transaction' => [
                    'status' => 'failed',
                    'message' => 'Transaction refusée par l\'opérateur',
                ],
            ]),
        );

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'échec',
            'bamboo_message' => 'Transaction refusée par l\'opérateur',
        ]);
    }

    public function test_check_status_falls_back_to_root_message(): void
    {
        $paiement = $this->createPendingPayment();

        $service = app(PaymentSettlementService::class);
        $service->settleFromBambooStatus(
            $paiement,
            'pending',
            PaymentSettlementService::messageFromCheckStatusResponse([
                'message' => 'Paiement en cours de traitement',
            ]),
        );

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'en_attente',
            'bamboo_message' => 'Paiement en cours de traitement',
        ]);
    }
}
