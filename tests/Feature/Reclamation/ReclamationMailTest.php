<?php

namespace Tests\Feature\Reclamation;

use App\Mail\Reclamation\ReclamationCreatedAdminMail;
use App\Mail\Reclamation\ReclamationCreatedAgenceMail;
use App\Mail\Reclamation\ReclamationStatutChangedClientMail;
use App\Models\Client;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\Agence\AgenceApiTestCase;

class ReclamationMailTest extends AgenceApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['verga.mail.admin_address' => 'admin@verga.test']);
    }

    public function test_client_reclamation_creation_queues_admin_and_agence_mails(): void
    {
        Mail::fake();

        ['token' => $token] = $this->createAuthenticatedClient([
            'email' => 'client-reclamation@example.com',
        ]);

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence-reclamation@test.com',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/client/reclamations', [
                'agence_id' => $agence->id,
                'objet' => 'Colis endommagé',
                'description' => 'Le carton est arrivé abîmé.',
            ])
            ->assertCreated();

        Mail::assertQueued(ReclamationCreatedAdminMail::class, fn ($mail) => $mail->hasTo('admin@verga.test'));
        Mail::assertQueued(ReclamationCreatedAgenceMail::class, fn ($mail) => $mail->hasTo('agence-reclamation@test.com'));
    }

    public function test_admin_statut_update_queues_client_mail(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'email' => 'agence@test.com',
        ]);

        $reclamation = Reclamation::create([
            'agence_id' => $agence->id,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0622222222',
            'email' => 'client-reclamation@example.com',
            'objet' => 'Retard livraison',
            'description' => 'Le colis est en retard.',
            'statut' => 'ouverte',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.reclamations.statut', $reclamation), [
                'statut' => 'en_cours',
            ])
            ->assertRedirect();

        Mail::assertQueued(ReclamationStatutChangedClientMail::class, function (ReclamationStatutChangedClientMail $mail): bool {
            return $mail->hasTo('client-reclamation@example.com')
                && $mail->nouveauStatut === 'en_cours';
        });
    }

    public function test_agence_statut_update_queues_client_mail(): void
    {
        Mail::fake();

        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence([
            'email' => 'agence-api@test.com',
        ]);

        $reclamation = $agence->reclamations()->create([
            'nom' => 'Mba',
            'prenom' => 'Paul',
            'telephone' => '0612345678',
            'email' => 'client-agence-reclamation@example.com',
            'objet' => 'Facturation',
            'description' => 'Montant incorrect sur la facture.',
            'statut' => 'ouverte',
        ]);

        $this->withToken($token)
            ->patchJson("/api/v1/agence/reclamations/{$reclamation->id}/statut", [
                'statut' => 'en_cours',
            ])
            ->assertOk();

        Mail::assertQueued(ReclamationStatutChangedClientMail::class, function (ReclamationStatutChangedClientMail $mail): bool {
            return $mail->hasTo('client-agence-reclamation@example.com')
                && $mail->nouveauStatut === 'en_cours';
        });
    }

    /**
     * @return array{user: User, client: Client, token: string}
     */
    private function createAuthenticatedClient(array $clientAttributes = []): array
    {
        $user = User::factory()->create([
            'role' => 'client',
            'password' => Hash::make('password'),
        ]);

        $client = Client::create(array_merge([
            'user_id' => $user->id,
            'nom' => 'Mba',
            'prenom' => 'Paul',
            'email' => $user->email,
            'telephone' => '0612345678',
            'statut' => 'actif',
        ], $clientAttributes));

        $token = $user->createToken('test')->plainTextToken;

        return compact('user', 'client', 'token');
    }
}
