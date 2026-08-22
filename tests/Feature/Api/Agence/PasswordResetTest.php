<?php

namespace Tests\Feature\Api\Agence;

use App\Notifications\Auth\AgenceResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_agence_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        ['owner' => $agenceUser] = $this->createTestAgence([], [
            'email' => 'gerant-reset@agence.test',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/agence/password/forgot', [
            'email' => $agenceUser->email,
        ])->assertOk();

        Notification::assertSentTo($agenceUser, AgenceResetPasswordNotification::class);
    }

    public function test_agence_forgot_password_is_silent_for_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/agence/password/forgot', [
            'email' => 'unknown@example.com',
        ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_agence_user_can_reset_password_with_valid_token(): void
    {
        ['owner' => $agenceUser] = $this->createTestAgence([], [
            'email' => 'gerant-reset@agence.test',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker('agence_users')->createToken($agenceUser);

        $this->postJson('/api/v1/agence/password/reset', [
            'token' => $token,
            'email' => $agenceUser->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $agenceUser->refresh();

        $this->assertTrue(Hash::check('new-password-123', $agenceUser->password));
    }
}
