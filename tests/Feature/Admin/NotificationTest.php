<?php

namespace Tests\Feature\Admin;

use App\Mail\Admin\MassNotificationMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_view_mass_mail_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.notifications.masse.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/notifications/masse/index')
                ->has('stats.clients')
                ->has('stats.agences')
            );
    }

    public function test_admin_can_view_targeted_mail_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.notifications.cible.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/notifications/cible/index')
                ->has('clients')
                ->has('agences')
            );
    }

    public function test_admin_can_queue_manual_mass_mail(): void
    {
        Mail::fake();
        $this->actingAsAdmin();

        $this->post(route('admin.notifications.masse.send'), [
            'audience' => 'manual',
            'subject' => 'Annonce VERGA',
            'message' => "Bonjour,\n\nMessage de test.",
            'emails' => "client1@test.com, client2@test.com\ninvalid-email",
            'action_label' => 'Voir le site',
            'action_url' => 'https://verga.test',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(MassNotificationMail::class, 2);
        Mail::assertQueued(MassNotificationMail::class, fn ($mail) => $mail->hasTo('client1@test.com'));
        Mail::assertQueued(MassNotificationMail::class, fn ($mail) => $mail->hasTo('client2@test.com'));
    }

    public function test_admin_can_queue_mass_mail_to_clients(): void
    {
        Mail::fake();
        $this->actingAsAdmin();

        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'client-mass@test.com',
        ]);

        Client::create([
            'user_id' => $user->id,
            'nom' => 'Mba',
            'prenom' => 'Paul',
            'email' => 'client-mass@test.com',
            'telephone' => '0612345678',
            'statut' => 'actif',
        ]);

        $this->post(route('admin.notifications.masse.send'), [
            'audience' => 'clients',
            'subject' => 'Info clients',
            'message' => 'Message groupé clients.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(MassNotificationMail::class, fn ($mail) => $mail->hasTo('client-mass@test.com'));
    }

    public function test_admin_can_queue_targeted_mail_to_client(): void
    {
        Mail::fake();
        $this->actingAsAdmin();

        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'client-cible@test.com',
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'email' => 'client-cible@test.com',
            'telephone' => '0622222222',
            'statut' => 'actif',
        ]);

        $this->post(route('admin.notifications.cible.send'), [
            'recipient_type' => 'client',
            'client_id' => $client->id,
            'subject' => 'Message personnel',
            'message' => 'Bonjour Sarah.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(MassNotificationMail::class, fn ($mail) => $mail->hasTo('client-cible@test.com'));
    }

    public function test_admin_can_queue_targeted_mail_to_custom_email(): void
    {
        Mail::fake();
        $this->actingAsAdmin();

        $this->post(route('admin.notifications.cible.send'), [
            'recipient_type' => 'email',
            'email' => 'contact@example.com',
            'subject' => 'Message direct',
            'message' => 'Contenu du message.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(MassNotificationMail::class, fn ($mail) => $mail->hasTo('contact@example.com'));
    }

    public function test_manual_mass_mail_requires_valid_emails_field(): void
    {
        $this->actingAsAdmin();

        $this->from(route('admin.notifications.masse.index'))
            ->post(route('admin.notifications.masse.send'), [
                'audience' => 'manual',
                'subject' => 'Test',
                'message' => 'Contenu',
                'emails' => '',
            ])
            ->assertRedirect(route('admin.notifications.masse.index'))
            ->assertSessionHasErrors('emails');
    }
}
