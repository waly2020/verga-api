<?php

namespace App\Services;

use App\Mail\Reversement\ReversementCreatedAgenceMail;
use App\Mail\Reversement\ReversementEffectueAgenceMail;
use App\Models\Reversement;

class ReversementMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function notifyCreated(Reversement $reversement): void
    {
        $email = $this->gerantEmail($reversement);

        if (! $email) {
            return;
        }

        $reversement->loadMissing(['agence.proprietaire']);

        $this->mail->queue(
            new ReversementCreatedAgenceMail($reversement),
            $email,
        );
    }

    public function notifyEffectue(Reversement $reversement): void
    {
        $email = $this->gerantEmail($reversement);

        if (! $email) {
            return;
        }

        $reversement->loadMissing(['agence.proprietaire']);

        $this->mail->queue(
            new ReversementEffectueAgenceMail($reversement),
            $email,
        );
    }

    private function gerantEmail(Reversement $reversement): ?string
    {
        $reversement->loadMissing('agence.proprietaire');

        $email = $reversement->agence?->proprietaire?->email;

        if (! is_string($email) || $email === '') {
            return null;
        }

        return $email;
    }
}
