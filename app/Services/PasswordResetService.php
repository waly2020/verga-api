<?php

namespace App\Services;

use App\Models\AgenceUser;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    /**
     * @param  array{email: string}  $credentials
     */
    public function sendClientResetLink(array $credentials): void
    {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('role', 'client')
            ->first();

        if (! $user) {
            return;
        }

        Password::broker('users')->sendResetLink(['email' => $user->email]);
    }

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $credentials
     */
    public function resetClientPassword(array $credentials): string
    {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('role', 'client')
            ->first();

        if (! $user) {
            return Password::INVALID_USER;
        }

        return Password::broker('users')->reset($credentials, function (User $user, string $password): void {
            if (! $user->isClient()) {
                throw ValidationException::withMessages([
                    'email' => ['Ce compte ne peut pas être réinitialisé via cet espace.'],
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            $user->tokens()->delete();

            event(new PasswordReset($user));
        });
    }

    /**
     * @param  array{email: string}  $credentials
     */
    public function sendAgenceResetLink(array $credentials): void
    {
        $user = AgenceUser::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user) {
            return;
        }

        Password::broker('agence_users')->sendResetLink(['email' => $user->email]);
    }

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $credentials
     */
    public function resetAgencePassword(array $credentials): string
    {
        return Password::broker('agence_users')->reset($credentials, function (AgenceUser $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            $user->tokens()->delete();

            event(new PasswordReset($user));
        });
    }
}
