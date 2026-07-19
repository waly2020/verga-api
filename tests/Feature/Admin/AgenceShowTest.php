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

class AgenceShowTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_admin_agence_show_displays_finance_stats_from_views(): void
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
            'code' => 'CMD-ADMIN-SHOW-001',
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 1,
            'montant_total' => 50000,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-ADMIN-SHOW-001',
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

        $this->actingAs($admin)
            ->get(route('admin.agences.show', $agence))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/agences/show')
                ->where('agence.id', $agence->id)
                ->where('stats.montant_paiements_valides', 45125)
                ->where('stats.montant_reversements', 15000)
                ->where('stats.montant_solde', 30125)
            );
    }
}
