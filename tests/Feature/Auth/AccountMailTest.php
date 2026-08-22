<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\AgenceCollaborateurWelcomeMail;
use App\Mail\Auth\AgenceStatutChangedMail;
use App\Mail\Auth\AgenceWelcomeMail;
use App\Mail\Auth\ClientWelcomeMail;
use App\Mail\Auth\CollaborateurWelcomeMail;
use App\Models\AgenceRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class AccountMailTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_collaborateur_creation_queues_welcome_mail(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.collaborateurs.store'), [
                'name' => 'Nouveau Collab',
                'email' => 'collab@verga.test',
                'role' => 'collaborateur',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.collaborateurs.index'));

        Mail::assertQueued(CollaborateurWelcomeMail::class, function (CollaborateurWelcomeMail $mail): bool {
            return $mail->hasTo('collab@verga.test')
                && $mail->user->name === 'Nouveau Collab';
        });
    }

    public function test_client_register_queues_welcome_mail(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/client/register', [
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'email' => 'sarah-mail@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'telephone' => '0622222222',
            'type' => 'particulier',
        ])->assertCreated();

        Mail::assertQueued(ClientWelcomeMail::class, function (ClientWelcomeMail $mail): bool {
            return $mail->hasTo('sarah-mail@example.com');
        });
    }

    public function test_agence_register_queues_welcome_mail(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/agence/register', [
            'nom' => 'Transit Mail',
            'email' => 'contact@transit-mail.test',
            'telephone' => '0612345678',
            'gerant_name' => 'Jean Mbaye',
            'gerant_email' => 'gerant@transit-mail.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        Mail::assertQueued(AgenceWelcomeMail::class, function (AgenceWelcomeMail $mail): bool {
            return $mail->hasTo('gerant@transit-mail.test');
        });
    }

    public function test_agence_collaborateur_creation_queues_welcome_mail(): void
    {
        Mail::fake();

        ['owner' => $owner, 'agence' => $agence] = $this->createTestAgence([], [
            'email' => 'owner@agence.test',
            'password' => Hash::make('password'),
        ]);

        $role = $this->createAgenceRole('operations');

        $token = $owner->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/agence/users', [
                'name' => 'Agent Terrain',
                'email' => 'agent@agence.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'agence_role_id' => $role->id,
            ])
            ->assertCreated();

        Mail::assertQueued(AgenceCollaborateurWelcomeMail::class, function (AgenceCollaborateurWelcomeMail $mail) use ($agence): bool {
            return $mail->hasTo('agent@agence.test')
                && $mail->agence->is($agence);
        });
    }

    public function test_agence_block_queues_statut_mail_to_owner(): void
    {
        Mail::fake();

        ['agence' => $agence] = $this->createTestAgence([
            'statut' => 'actif',
        ], [
            'email' => 'gerant-block@agence.test',
            'password' => Hash::make('password'),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.agences.statut', $agence))
            ->assertRedirect();

        Mail::assertQueued(AgenceStatutChangedMail::class, function (AgenceStatutChangedMail $mail): bool {
            return $mail->hasTo('gerant-block@agence.test')
                && $mail->nouveauStatut === 'bloqué';
        });
    }

    private function createAgenceRole(string $slug): AgenceRole
    {
        return AgenceRole::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'nom' => ucfirst($slug),
                'description' => "Rôle {$slug} créé pour les tests.",
                'actif' => true,
                'est_systeme' => false,
            ],
        );
    }
}
