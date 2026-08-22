<?php

namespace App\Mail\Publicite;

use App\Models\PaiementPublicite;
use App\Models\Publicite;
use App\Support\PubliciteMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PublicitePaiementEchecOwnerMail extends PubliciteMail
{
    public function __construct(
        Publicite $publicite,
        public PaiementPublicite $paiement,
    ) {
        parent::__construct($publicite);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Échec du paiement publicité — '.$this->publicite->titre,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.publicite.paiement-echec-owner',
            with: [
                ...$this->sharedData(),
                'publiciteUrl' => PubliciteMailUrls::ownerShow($this->publicite),
                'paiementCode' => $this->paiement->code,
                'montantTotal' => $this->formatMontant((float) $this->paiement->montant),
            ],
        );
    }
}
