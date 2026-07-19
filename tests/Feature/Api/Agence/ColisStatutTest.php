<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ColisStatutTest extends AgenceApiTestCase
{
    use RefreshDatabase;

    private function createColisForAgence(string $statut = 'chez_client'): array
    {
        ['agence' => $agence, 'user' => $user, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre colis',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $this->createClient()->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-COLIS-'.uniqid(),
            'quantite' => 5,
            'montant_total' => 43750,
            'statut' => 'confirmée',
        ]);

        $colis = Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-'.uniqid(),
            'statut' => $statut,
        ]);

        return compact('agence', 'user', 'token', 'colis');
    }

    public function test_agence_can_advance_colis_through_full_tracking_flow(): void
    {
        ['token' => $token, 'user' => $user, 'colis' => $colis] = $this->createColisForAgence();

        $steps = [
            ['from' => 'chez_client', 'to' => 'déposé', 'next' => 'en_transit'],
            ['from' => 'déposé', 'to' => 'en_transit', 'next' => 'arrivé'],
            ['from' => 'en_transit', 'to' => 'arrivé', 'next' => 'récupéré'],
            ['from' => 'arrivé', 'to' => 'récupéré', 'next' => null],
        ];

        foreach ($steps as $step) {
            $this->withAgenceToken($token)
                ->patchJson("/api/v1/agence/colis/{$colis->id}/statut", [
                    'statut' => $step['to'],
                    'commentaire' => "Passage à {$step['to']}",
                ])
                ->assertOk()
                ->assertJsonPath('data.statut', $step['to'])
                ->assertJsonPath('next_statut', $step['next']);

            $this->assertDatabaseHas('historique_colis', [
                'colis_id' => $colis->id,
                'actor_type' => 'agence_user',
                'actor_id' => $user->id,
                'statut' => $step['to'],
            ]);

            $colis->refresh();
        }
    }

    public function test_agence_can_advance_without_explicit_statut(): void
    {
        ['token' => $token, 'colis' => $colis] = $this->createColisForAgence();

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut")
            ->assertOk()
            ->assertJsonPath('data.statut', 'déposé');
    }

    public function test_agence_can_capture_date_statut_on_status_change(): void
    {
        ['token' => $token, 'colis' => $colis] = $this->createColisForAgence();

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut", [
                'statut' => 'déposé',
                'date_statut' => '2026-07-20',
                'commentaire' => 'Déposé le 20',
            ])
            ->assertOk()
            ->assertJsonPath('data.historique.0.date_statut', '2026-07-20');

        $historique = $colis->historique()->where('statut', 'déposé')->first();

        $this->assertNotNull($historique);
        $this->assertSame('2026-07-20', $historique->date_statut?->toDateString());
    }

    public function test_cannot_advance_when_final_statut_reached(): void
    {
        ['token' => $token, 'colis' => $colis] = $this->createColisForAgence('récupéré');

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['statut']);
    }

    public function test_rejects_invalid_statut_transition(): void
    {
        ['token' => $token, 'colis' => $colis] = $this->createColisForAgence();

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut", [
                'statut' => 'récupéré',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['statut']);
    }

    public function test_other_agence_cannot_update_colis(): void
    {
        ['colis' => $colis] = $this->createColisForAgence();
        ['token' => $otherToken] = $this->createAuthenticatedAgence([
            'email' => 'autre@transit.test',
        ]);

        $this->withAgenceToken($otherToken)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut")
            ->assertNotFound();
    }
}
