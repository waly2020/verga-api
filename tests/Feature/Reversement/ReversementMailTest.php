<?php

namespace Tests\Feature\Reversement;

use App\Mail\Reversement\ReversementCreatedAgenceMail;
use App\Mail\Reversement\ReversementEffectueAgenceMail;
use App\Models\Agence;
use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Reversement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class ReversementMailTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_store_queues_created_mail_to_agence_owner(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        ['agence' => $agence, 'owner' => $owner] = $this->createTestAgence([], [
            'email' => 'gerant-reversement@test.com',
        ]);

        $this->createAgenceSolde($agence);

        $this->actingAs($admin)
            ->post(route('admin.reversements.store'), [
                'agence_id' => $agence->id,
                'montant' => 15000,
                'periode' => '2026-07',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(ReversementCreatedAgenceMail::class, fn ($mail) => $mail->hasTo('gerant-reversement@test.com'));
    }

    public function test_effectuer_queues_completed_mail_to_agence_owner(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        ['agence' => $agence, 'owner' => $owner] = $this->createTestAgence([], [
            'email' => 'gerant-effectue@test.com',
        ]);

        $this->createAgenceSolde($agence);

        $reversement = Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 15000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reversements.effectuer', $reversement))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(ReversementEffectueAgenceMail::class, fn ($mail) => $mail->hasTo('gerant-effectue@test.com'));
    }

    public function test_store_skips_mail_without_owner_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        ['agence' => $agence] = $this->createTestAgence([], [
            'email' => '',
        ]);

        $this->createAgenceSolde($agence);

        $this->actingAs($admin)
            ->post(route('admin.reversements.store'), [
                'agence_id' => $agence->id,
                'montant' => 10000,
                'periode' => '2026-08',
            ])
            ->assertRedirect();

        Mail::assertNothingQueued();
    }

    private function createAgenceSolde(Agence $agence): void
    {
        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Groupage reversement mail',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-REV-MAIL-'.uniqid(),
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 1,
            'montant_total' => 50000,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-REV-MAIL-'.uniqid(),
            'montant' => 50000,
            'montant_sous_total' => 47500,
            'montant_commission_client' => 2500,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
        ]);
    }
}
