<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Agence;
use App\Models\Reversement;
use App\Models\User;
use App\Services\Finance\AgenceSoldeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgenceSoldeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_solde_disponible_subtracts_pending_reversements(): void
    {
        $agenceUser = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $agenceUser->id,
            'nom' => 'Agence Test',
            'email' => 'agence@test.com',
            'telephone' => '0612345678',
            'statut' => 'actif',
        ]);

        Reversement::create([
            'agence_id' => $agence->id,
            'montant' => 5000,
            'periode' => '2026-07',
            'statut' => 'en_attente',
        ]);

        $this->assertSame(0.0, AgenceSoldeService::soldeCourant($agence->id));
        $this->assertSame(5000.0, AgenceSoldeService::montantReversementsEnAttente($agence->id));
        $this->assertSame(0.0, AgenceSoldeService::soldeDisponible($agence->id));
        $this->assertFalse(AgenceSoldeService::peutReverser($agence->id, 1));
    }
}
