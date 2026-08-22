<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait BuildsFrenchResetPasswordMail
{
    protected function buildFrenchResetPasswordMail(string $url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->line('Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line("Ce lien expirera dans {$minutes} minutes.")
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, aucune action n\'est requise.');
    }
}
