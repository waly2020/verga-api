<?php

namespace Tests\Feature\Database;

use App\Models\Agence;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\Reversement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class AgenceFinanceViewsTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_paiements_valides_view_sums_only_validated_payments_per_agence(): void
    {
        $agence = $this->createAgence();
        $commande = $this->createCommande($agence);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-VIEW-001',
            'montant' => 25000,
            'montant_sous_total' => 23750,
            'montant_commission_client' => 1250,
            'montant_commission_agence' => 1187.50,
            'montant_agence' => 22562.50,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-VIEW-002',
            'montant' => 10000,
            'montant_sous_total' => 9500,
            'montant_commission_client' => 500,
            'methode' => 'bamboo_redirect',
            'statut' => 'échec',
        ]);

        $row = DB::table('vue_agences_paiements_valides')
            ->where('agence_id', $agence->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(23750.0, (float) $row->montant_sous_total);
        $this->assertSame(1187.5, (float) $row->montant_commission_agence);
        $this->assertSame(22562.5, (float) $row->montant_agence);
        $this->assertSame(1, (int) $row->nb_paiements);
    }

    public function test_reversements_view_sums_only_effectue_reversements_per_agence(): void
    {
        $agence = $this->createAgence();

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 10000,
            'periode' => '2026-06',
            'statut' => 'effectué',
            'effectue_le' => now(),
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 5000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $row = DB::table('vue_agences_reversements')
            ->where('agence_id', $agence->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(10000.0, (float) $row->montant);
        $this->assertSame(1, (int) $row->nb_reversements);
    }

    public function test_soldes_view_equals_paiements_valides_minus_reversements(): void
    {
        $agence = $this->createAgence();
        $commande = $this->createCommande($agence);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-VIEW-003',
            'montant' => 50000,
            'montant_sous_total' => 47500,
            'montant_commission_client' => 2500,
            'montant_commission_agence' => 2375,
            'montant_agence' => 45125,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-06',
            'statut' => 'effectué',
            'effectue_le' => now(),
        ]);

        $row = DB::table('vue_agences_soldes')
            ->where('agence_id', $agence->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(45125.0, (float) $row->montant_paiements_valides);
        $this->assertSame(15000.0, (float) $row->montant_reversements);
        $this->assertSame(30125.0, (float) $row->montant_solde);
    }

    public function test_agence_without_activity_has_zero_balances_in_views(): void
    {
        $agence = $this->createAgence();

        $paiements = DB::table('vue_agences_paiements_valides')
            ->where('agence_id', $agence->id)
            ->first();

        $reversements = DB::table('vue_agences_reversements')
            ->where('agence_id', $agence->id)
            ->first();

        $solde = DB::table('vue_agences_soldes')
            ->where('agence_id', $agence->id)
            ->first();

        $this->assertSame(0.0, (float) $paiements->montant_sous_total);
        $this->assertSame(0.0, (float) $paiements->montant_agence);
        $this->assertSame(0.0, (float) $reversements->montant);
        $this->assertSame(0.0, (float) $solde->montant_solde);
    }

    private function createAgence(): Agence
    {
        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Agence Test Views',
            'telephone' => '0612345678',
        ]);

        return $agence;
    }

    private function createCommande(Agence $agence): Commande
    {
        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre views',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        return Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-VIEW-'.fake()->unique()->numerify('###'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 1,
            'montant_total' => 8750,
            'statut' => 'confirmée',
        ]);
    }
}
