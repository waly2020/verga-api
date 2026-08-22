<?php

namespace App\Services;

use App\Mail\Publicite\PubliciteExpireeOwnerMail;
use App\Mail\Publicite\PublicitePaiementEchecOwnerMail;
use App\Mail\Publicite\PublicitePublieeOwnerMail;
use App\Mail\Publicite\PubliciteRefuseeOwnerMail;
use App\Mail\Publicite\PubliciteRetireeOwnerMail;
use App\Mail\Publicite\PubliciteSubmittedAdminMail;
use App\Mail\Publicite\PubliciteValideeOwnerMail;
use App\Models\PaiementPublicite;
use App\Models\Publicite;
use Illuminate\Mail\Mailable;

class PubliciteMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function notifySubmitted(Publicite $publicite): void
    {
        $adminAddress = config('verga.mail.admin_address');

        if (! is_string($adminAddress) || $adminAddress === '') {
            return;
        }

        $publicite->loadMissing(['agence', 'client']);

        $this->mail->queue(
            new PubliciteSubmittedAdminMail($publicite),
            $adminAddress,
        );
    }

    public function notifyValidee(Publicite $publicite): void
    {
        $this->queueToOwner(
            $publicite,
            new PubliciteValideeOwnerMail($publicite),
        );
    }

    public function notifyRefusee(Publicite $publicite): void
    {
        $this->queueToOwner(
            $publicite,
            new PubliciteRefuseeOwnerMail($publicite),
        );
    }

    public function notifyPubliee(Publicite $publicite, ?PaiementPublicite $paiement = null): void
    {
        $this->queueToOwner(
            $publicite,
            new PublicitePublieeOwnerMail($publicite, $paiement),
        );
    }

    public function notifyPaiementEchec(Publicite $publicite, PaiementPublicite $paiement): void
    {
        $this->queueToOwner(
            $publicite,
            new PublicitePaiementEchecOwnerMail($publicite, $paiement),
        );
    }

    public function notifyRetiree(Publicite $publicite): void
    {
        if (! $this->hasOwner($publicite)) {
            return;
        }

        $this->queueToOwner(
            $publicite,
            new PubliciteRetireeOwnerMail($publicite),
        );
    }

    public function notifyExpiree(Publicite $publicite): void
    {
        if (! $this->hasOwner($publicite)) {
            return;
        }

        $this->queueToOwner(
            $publicite,
            new PubliciteExpireeOwnerMail($publicite),
        );
    }

    private function queueToOwner(Publicite $publicite, Mailable $mailable): void
    {
        $email = $this->ownerEmail($publicite);

        if (! $email) {
            return;
        }

        $publicite->loadMissing(['agence', 'client.user', 'offre']);

        $this->mail->queue($mailable, $email);
    }

    private function hasOwner(Publicite $publicite): bool
    {
        return $publicite->agence_id !== null || $publicite->client_id !== null;
    }

    private function ownerEmail(Publicite $publicite): ?string
    {
        if ($publicite->agence_id) {
            $email = $publicite->agence?->email;

            return is_string($email) && $email !== '' ? $email : null;
        }

        if ($publicite->client_id) {
            $email = $publicite->client?->email ?? $publicite->client?->user?->email;

            return is_string($email) && $email !== '' ? $email : null;
        }

        return null;
    }
}
