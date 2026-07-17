<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Agence;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\Reversement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class ReversementTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_create_reversement_en_attente_without_impacting_solde(): void
    {
        $this->actingAsAdmin();
        $agence = $this->createAgenceWithSolde(47500);

        $this->post(route('admin.reversements.store'), [
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-07',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reversements', [
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->assertSame(47500.0, $this->soldeCourant($agence->id));
    }

    public function test_admin_cannot_create_reversement_above_available_balance(): void
    {
        $this->actingAsAdmin();
        $agence = $this->createAgenceWithSolde(47500);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 20000,
            'periode' => '2026-06',
            'statut' => 'en_attente',
        ]);

        $this->from(route('admin.reversements.index'))
            ->post(route('admin.reversements.store'), [
                'agence_id' => $agence->id,
                'montant' => 30000,
                'periode' => '2026-07',
            ])
            ->assertRedirect(route('admin.reversements.index'))
            ->assertSessionHasErrors('montant');
    }

    public function test_admin_can_validate_reversement_and_impact_solde(): void
    {
        $admin = $this->actingAsAdmin();
        $agence = $this->createAgenceWithSolde(47500);

        $reversement = Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->patch(route('admin.reversements.effectuer', $reversement))
            ->assertRedirect()
            ->assertSessionHas('success');

        $reversement->refresh();

        $this->assertSame('effectué', $reversement->statut);
        $this->assertSame($admin->id, $reversement->admin_id);
        $this->assertNotNull($reversement->effectue_le);
        $this->assertSame(32500.0, $this->soldeCourant($agence->id));
    }

    public function test_admin_cannot_validate_reversement_when_balance_is_insufficient(): void
    {
        $this->actingAsAdmin();
        $agence = $this->createAgenceWithSolde(10000);

        $reversement = Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->from(route('admin.reversements.index'))
            ->patch(route('admin.reversements.effectuer', $reversement))
            ->assertRedirect(route('admin.reversements.index'))
            ->assertSessionHas('error');

        $reversement->refresh();
        $this->assertSame('en_attente', $reversement->statut);
        $this->assertSame(10000.0, $this->soldeCourant($agence->id));
    }

    public function test_reversements_index_includes_agences_with_available_balance(): void
    {
        $this->actingAsAdmin();
        $agence = $this->createAgenceWithSolde(47500);

        $this->get(route('admin.reversements.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reversements/index')
                ->has('agences', 1)
                ->where('agences.0.id', $agence->id)
                ->where('agences.0.montant_solde', 47500)
                ->where('agences.0.montant_disponible', 47500)
            );
    }

    private function createAgenceWithSolde(float $montantSousTotal): Agence
    {
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
            'code' => 'CMD-REV-'.fake()->unique()->numerify('###'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 1,
            'montant_total' => 50000,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-REV-'.fake()->unique()->numerify('###'),
            'montant' => 50000,
            'montant_sous_total' => $montantSousTotal,
            'montant_commission_client' => 2500,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);

        return $agence;
    }

    private function soldeCourant(string $agenceId): float
    {
        return (float) DB::table('vue_agences_soldes')
            ->where('agence_id', $agenceId)
            ->value('montant_solde');
    }
}
