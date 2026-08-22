<?php

namespace App\Mail\Publicite;

use App\Models\PaiementPublicite;
use App\Models\Publicite;
use App\Support\PubliciteMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PublicitePublieeOwnerMail extends PubliciteMail
{
    public function __construct(
        Publicite $publicite,
        public ?PaiementPublicite $paiement = null,
    ) {
        parent::__construct($publicite);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Publicité en ligne — '.$this->publicite->titre,
        );
    }

    public function content(): Content
    {
        $this->publicite->refresh();

        return new Content(
            markdown: 'mail.publicite.publiee-owner',
            with: [
                ...$this->sharedData(),
                'publiciteUrl' => PubliciteMailUrls::ownerShow($this->publicite),
                'paiementCode' => $this->paiement?->code,
                'montantTotal' => $this->paiement
                    ? $this->formatMontant((float) $this->paiement->montant)
                    : null,
                'retourUrl' => $this->paiement
                    ? PubliciteMailUrls::paiementRetour($this->paiement)
                    : null,
                'periodeTerminee' => $this->publicite->statut === Publicite::STATUT_EXPIREE,
            ],
        );
    }
}
