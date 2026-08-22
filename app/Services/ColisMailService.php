<?php

namespace App\Services;

use App\Mail\Colis\ColisStatutChangedClientMail;
use App\Models\Colis;

class ColisMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function notifyStatutAdvanced(Colis $colis, string $nouveauStatut): void
    {
        $colis->loadMissing(['commande.client.user', 'commande.offre', 'agence']);

        $email = $this->clientEmail($colis);

        if (! $email) {
            return;
        }

        $this->mail->queue(
            new ColisStatutChangedClientMail($colis, $nouveauStatut),
            $email,
        );
    }

    private function clientEmail(Colis $colis): ?string
    {
        $commande = $colis->commande;

        if (! $commande) {
            return null;
        }

        $email = $commande->client?->email ?? $commande->client?->user?->email;

        if (! is_string($email) || $email === '') {
            return null;
        }

        return $email;
    }
}
