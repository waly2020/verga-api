<?php

namespace App\Services;

use App\Mail\Reclamation\ReclamationCreatedAdminMail;
use App\Mail\Reclamation\ReclamationCreatedAgenceMail;
use App\Mail\Reclamation\ReclamationStatutChangedClientMail;
use App\Models\Reclamation;

class ReclamationMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function notifyCreated(Reclamation $reclamation): void
    {
        $reclamation->loadMissing(['agence', 'commande', 'client']);

        $adminAddress = config('verga.mail.admin_address');

        if (is_string($adminAddress) && $adminAddress !== '') {
            $this->mail->queue(
                new ReclamationCreatedAdminMail($reclamation),
                $adminAddress,
            );
        }

        if ($reclamation->agence?->email) {
            $this->mail->queue(
                new ReclamationCreatedAgenceMail($reclamation),
                $reclamation->agence->email,
            );
        }
    }

    public function notifyStatutChanged(Reclamation $reclamation, string $nouveauStatut): void
    {
        $email = $this->clientEmail($reclamation);

        if (! $email) {
            return;
        }

        $reclamation->loadMissing(['agence', 'commande']);

        $this->mail->queue(
            new ReclamationStatutChangedClientMail($reclamation, $nouveauStatut),
            $email,
        );
    }

    private function clientEmail(Reclamation $reclamation): ?string
    {
        $email = $reclamation->email ?? $reclamation->client?->email;

        if (! is_string($email) || $email === '') {
            return null;
        }

        return $email;
    }
}
