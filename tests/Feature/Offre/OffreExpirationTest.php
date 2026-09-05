<?php

namespace Tests\Feature\Offre;

use App\Jobs\DesactiverOffresDepartPassees;
use App\Services\OffreExpirationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class OffreExpirationTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_desactive_offres_dont_le_depart_est_aujourdhui_ou_passe(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 23:59:00', OffreExpirationService::TIMEZONE));

        ['agence' => $agence] = $this->createTestAgence();

        $passee = $this->createOffreForAgence($agence, [
            'titre' => 'Départ hier',
            'date_depart' => '2026-09-04',
            'statut' => 'active',
        ]);
        $aujourdHui = $this->createOffreForAgence($agence, [
            'titre' => 'Départ aujourd’hui',
            'date_depart' => '2026-09-05',
            'statut' => 'active',
        ]);
        $future = $this->createOffreForAgence($agence, [
            'titre' => 'Départ demain',
            'date_depart' => '2026-09-06',
            'statut' => 'active',
        ]);
        $sansDate = $this->createOffreForAgence($agence, [
            'titre' => 'Sans date',
            'date_depart' => null,
            'statut' => 'active',
        ]);

        $count = app(OffreExpirationService::class)->desactiverDepartPasses();

        $this->assertSame(2, $count);
        $this->assertSame('inactive', $passee->fresh()->statut);
        $this->assertSame('inactive', $aujourdHui->fresh()->statut);
        $this->assertSame('active', $future->fresh()->statut);
        $this->assertSame('active', $sansDate->fresh()->statut);
    }

    public function test_job_desactive_via_la_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 23:59:00', OffreExpirationService::TIMEZONE));

        ['agence' => $agence] = $this->createTestAgence();
        $offre = $this->createOffreForAgence($agence, [
            'date_depart' => '2026-09-01',
            'statut' => 'active',
        ]);

        (new DesactiverOffresDepartPassees)->handle(app(OffreExpirationService::class));

        $this->assertSame('inactive', $offre->fresh()->statut);
    }
}
