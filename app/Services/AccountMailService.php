<?php

namespace App\Services;

use App\Mail\Auth\AgenceCollaborateurWelcomeMail;
use App\Mail\Auth\AgenceStatutChangedMail;
use App\Mail\Auth\AgenceWelcomeMail;
use App\Mail\Auth\ClientWelcomeMail;
use App\Mail\Auth\CollaborateurWelcomeMail;
use App\Models\Agence;
use App\Models\AgenceUser;
use App\Models\User;

class AccountMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function notifyCollaborateurCreated(User $user): void
    {
        if (! $user->isAdmin() && $user->role !== 'collaborateur') {
            return;
        }

        $this->mail->queue(
            new CollaborateurWelcomeMail($user),
            $user->email,
        );
    }

    public function notifyClientRegistered(User $user): void
    {
        if (! $user->isClient()) {
            return;
        }

        $this->mail->queue(
            new ClientWelcomeMail($user),
            $user->email,
        );
    }

    public function notifyAgenceRegistered(AgenceUser $agenceUser, Agence $agence): void
    {
        $this->mail->queue(
            new AgenceWelcomeMail($agenceUser, $agence),
            $agenceUser->email,
        );
    }

    public function notifyAgenceCollaborateurCreated(AgenceUser $agenceUser, Agence $agence): void
    {
        if ($agenceUser->est_proprietaire) {
            return;
        }

        $agenceUser->loadMissing('role');

        $this->mail->queue(
            new AgenceCollaborateurWelcomeMail($agenceUser, $agence),
            $agenceUser->email,
        );
    }

    public function notifyAgenceStatutChanged(Agence $agence, string $nouveauStatut): void
    {
        $agence->loadMissing('proprietaire');

        $proprietaire = $agence->proprietaire;

        if (! $proprietaire) {
            return;
        }

        $this->mail->queue(
            new AgenceStatutChangedMail($agence, $nouveauStatut),
            $proprietaire->email,
        );
    }
}
