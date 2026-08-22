<?php

namespace Tests\Feature\Api\Client;

use App\Models\Client;
use App\Models\User;
use App\Notifications\Auth\ClientResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'client-reset@example.com',
        ]);

        Client::create([
            'user_id' => $user->id,
            'nom' => 'Client',
            'prenom' => 'Test',
            'email' => $user->email,
            'telephone' => '0600000000',
            'statut' => 'actif',
        ]);

        $this->postJson('/api/v1/client/password/forgot', [
            'email' => 'client-reset@example.com',
        ])->assertOk();

        Notification::assertSentTo($user, ClientResetPasswordNotification::class);
    }

    public function test_client_forgot_password_is_silent_for_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/client/password/forgot', [
            'email' => 'unknown@example.com',
        ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_client_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'client-reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        Client::create([
            'user_id' => $user->id,
            'nom' => 'Client',
            'prenom' => 'Test',
            'email' => $user->email,
            'telephone' => '0600000000',
            'statut' => 'actif',
        ]);

        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/v1/client/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_admin_email_cannot_be_reset_via_client_endpoint(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $token = Password::broker('users')->createToken($admin);

        $this->postJson('/api/v1/client/password/reset', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable();
    }
}
