<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\Reversement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_admin_dashboard_displays_agence_finance_charts_from_views(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit Libreville',
            'email' => 'libreville@transit.test',
            'telephone' => '0611111111',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Groupage',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-DASH-001',
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 1,
            'montant_total' => 50000,
            'statut' => 'confirmée',
            'created_at' => now(),
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-DASH-001',
            'montant' => 50000,
            'montant_sous_total' => 47500,
            'montant_commission_client' => 2500,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
            'created_at' => now(),
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-06',
            'statut' => 'effectué',
            'effectue_le' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->where('stats.soldes_agences_total', 32500)
                ->has('paiements_par_agence', 1)
                ->where('paiements_par_agence.0.nom', 'Transit Libreville')
                ->where('paiements_par_agence.0.total', 47500)
                ->has('soldes_par_agence', 1)
                ->where('soldes_par_agence.0.total', 32500)
                ->has('reversements_par_agence', 1)
                ->where('reversements_par_agence.0.total', 15000)
            );
    }
}
