<?php

namespace Tests\Feature\Colis;

use App\Mail\Colis\ColisStatutChangedClientMail;
use App\Models\Client;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\Agence\AgenceApiTestCase;

class ColisMailTest extends AgenceApiTestCase
{
    use RefreshDatabase;

    public function test_colis_status_advance_queues_mail_for_each_step(): void
    {
        Mail::fake();

        ['token' => $token, 'colis' => $colis] = $this->createColisWithClient('client-colis@example.com');

        foreach (['déposé', 'en_transit', 'arrivé', 'récupéré'] as $statut) {
            $this->withAgenceToken($token)
                ->patchJson("/api/v1/agence/colis/{$colis->id}/statut")
                ->assertOk()
                ->assertJsonPath('data.statut', $statut);

            Mail::assertQueued(ColisStatutChangedClientMail::class, function (ColisStatutChangedClientMail $mail) use ($statut): bool {
                return $mail->hasTo('client-colis@example.com')
                    && $mail->nouveauStatut === $statut;
            });
        }

        Mail::assertQueued(ColisStatutChangedClientMail::class, 4);
    }

    public function test_colis_status_advance_skips_mail_without_client_email(): void
    {
        Mail::fake();

        ['token' => $token, 'colis' => $colis] = $this->createColisWithClient(null);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut")
            ->assertOk();

        Mail::assertNothingQueued();
    }

    /**
     * @return array{token: string, colis: Colis}
     */
    private function createColisWithClient(?string $email): array
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre colis mail',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $client = $email
            ? $this->createClientWithEmail($email)
            : null;

        $commande = Commande::create([
            'client_id' => $client?->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-COLIS-'.uniqid(),
            'nom' => 'Mba',
            'prenom' => 'Paul',
            'telephone' => '0612345678',
            'quantite' => 5,
            'montant_total' => 43750,
            'statut' => 'confirmée',
        ]);

        $colis = Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-'.uniqid(),
            'statut' => 'chez_client',
        ]);

        return compact('token', 'colis');
    }

    private function createClientWithEmail(string $email): Client
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => $email,
        ]);

        return Client::create([
            'user_id' => $user->id,
            'nom' => 'Mba',
            'prenom' => 'Paul',
            'email' => $email,
            'telephone' => '0612345678',
            'statut' => 'actif',
        ]);
    }
}
