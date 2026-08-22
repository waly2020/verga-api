<?php

namespace Tests\Unit\Services;

use App\Models\PaiementPublicite;
use App\Models\Publicite;
use App\Services\PublicitePaymentSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class PublicitePaymentSettlementServiceTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_completed_payment_publishes_publicite(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub payée',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(5)->toDateString(),
            'nombre_jours' => 6,
            'statut' => Publicite::STATUT_VALIDEE,
            'statut_paiement' => Publicite::PAIEMENT_EN_ATTENTE,
        ]);

        $paiement = PaiementPublicite::create([
            'publicite_id' => $publicite->id,
            'code' => 'PUB-TEST001',
            'nombre_jours' => 6,
            'prix_par_jour' => 1000,
            'montant_sous_total' => 6000,
            'montant_frais' => 500,
            'montant' => 6500,
            'statut' => 'en_attente',
        ]);

        app(PublicitePaymentSettlementService::class)
            ->settleFromCallback([
                'billingId' => 'PUB-TEST001',
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('paiements_publicite', [
            'id' => $paiement->id,
            'statut' => 'validé',
        ]);
        $this->assertDatabaseHas('publicites', [
            'id' => $publicite->id,
            'statut' => Publicite::STATUT_PUBLIEE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);
    }
}
