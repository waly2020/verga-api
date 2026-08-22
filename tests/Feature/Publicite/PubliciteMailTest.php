<?php

namespace Tests\Feature\Publicite;

use App\Mail\Publicite\PubliciteExpireeOwnerMail;
use App\Mail\Publicite\PublicitePaiementEchecOwnerMail;
use App\Mail\Publicite\PublicitePublieeOwnerMail;
use App\Mail\Publicite\PubliciteRefuseeOwnerMail;
use App\Mail\Publicite\PubliciteRetireeOwnerMail;
use App\Mail\Publicite\PubliciteSubmittedAdminMail;
use App\Mail\Publicite\PubliciteValideeOwnerMail;
use App\Models\ConfigurationPublicite;
use App\Models\PaiementPublicite;
use App\Models\Publicite;
use App\Services\PubliciteLifecycleService;
use App\Services\PublicitePaymentSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class PubliciteMailTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        ConfigurationPublicite::create([
            'prix_par_jour' => 1000,
            'type_frais' => 'fixe',
            'valeur_frais' => 500,
            'actif' => true,
            'libelle' => 'Tarif test',
        ]);

        config(['verga.mail.admin_address' => 'admin@verga.test']);
    }

    public function test_agence_publicite_creation_queues_admin_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-pub@test.com',
        ]);

        app(PubliciteLifecycleService::class)->createForAgence($agence, [
            'titre' => 'Promo groupage',
            'description' => 'Offre spéciale',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(6)->toDateString(),
        ], UploadedFile::fake()->image('banner.jpg'));

        Mail::assertQueued(PubliciteSubmittedAdminMail::class, fn ($mail) => $mail->hasTo('admin@verga.test'));
    }

    public function test_validation_queues_owner_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-validee@test.com',
        ]);

        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub à valider',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
            'nombre_jours' => 4,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        app(PubliciteLifecycleService::class)->valider($publicite);

        Mail::assertQueued(PubliciteValideeOwnerMail::class, fn ($mail) => $mail->hasTo('agence-validee@test.com'));
    }

    public function test_refusal_queues_owner_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-refus@test.com',
        ]);

        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub refusée',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
            'nombre_jours' => 3,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        app(PubliciteLifecycleService::class)->refuser($publicite, 'Visuel non conforme');

        Mail::assertQueued(PubliciteRefuseeOwnerMail::class, fn ($mail) => $mail->hasTo('agence-refus@test.com'));
    }

    public function test_completed_payment_queues_owner_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-paiement@test.com',
        ]);

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
            'code' => 'PUB-MAIL001',
            'nombre_jours' => 6,
            'prix_par_jour' => 1000,
            'montant_sous_total' => 6000,
            'montant_frais' => 500,
            'montant' => 6500,
            'statut' => 'en_attente',
        ]);

        app(PublicitePaymentSettlementService::class)->settleFromCallback([
            'billingId' => 'PUB-MAIL001',
            'status' => 'completed',
        ]);

        Mail::assertQueued(PublicitePublieeOwnerMail::class, fn ($mail) => $mail->hasTo('agence-paiement@test.com'));
    }

    public function test_failed_payment_queues_owner_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-echec@test.com',
        ]);

        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub échec',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
            'nombre_jours' => 3,
            'statut' => Publicite::STATUT_VALIDEE,
            'statut_paiement' => Publicite::PAIEMENT_EN_ATTENTE,
        ]);

        $paiement = PaiementPublicite::create([
            'publicite_id' => $publicite->id,
            'code' => 'PUB-MAIL002',
            'nombre_jours' => 3,
            'prix_par_jour' => 1000,
            'montant_sous_total' => 3000,
            'montant_frais' => 500,
            'montant' => 3500,
            'statut' => 'en_attente',
        ]);

        app(PublicitePaymentSettlementService::class)->settleFromCallback([
            'billingId' => 'PUB-MAIL002',
            'status' => 'failed',
        ]);

        Mail::assertQueued(PublicitePaiementEchecOwnerMail::class, fn ($mail) => $mail->hasTo('agence-echec@test.com'));
    }

    public function test_withdrawal_queues_owner_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-retrait@test.com',
        ]);

        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub retirée',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(4)->toDateString(),
            'nombre_jours' => 5,
            'statut' => Publicite::STATUT_PUBLIEE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        app(PubliciteLifecycleService::class)->updateStatutByAdmin(
            $publicite,
            Publicite::STATUT_RETIREE,
            'Contenu inapproprié',
        );

        Mail::assertQueued(PubliciteRetireeOwnerMail::class, fn ($mail) => $mail->hasTo('agence-retrait@test.com'));
    }

    public function test_expire_overdue_queues_owner_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-expiree@test.com',
        ]);

        Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub expirée',
            'date_debut' => now()->subDays(10)->toDateString(),
            'date_fin' => now()->subDay()->toDateString(),
            'nombre_jours' => 10,
            'statut' => Publicite::STATUT_PUBLIEE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        $count = app(PubliciteLifecycleService::class)->expireOverdue();

        $this->assertSame(1, $count);
        Mail::assertQueued(PubliciteExpireeOwnerMail::class, fn ($mail) => $mail->hasTo('agence-expiree@test.com'));
    }

    public function test_admin_creation_does_not_queue_moderation_mail(): void
    {
        Mail::fake();

        app(PubliciteLifecycleService::class)->createByAdmin([
            'proprietaire' => 'verga',
            'titre' => 'Pub VERGA',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
        ], UploadedFile::fake()->image('verga.jpg'));

        Mail::assertNothingQueued();
    }
}
