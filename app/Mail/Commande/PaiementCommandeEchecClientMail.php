<?php

namespace App\Mail\Commande;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaiementCommandeEchecClientMail extends CommandeMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Échec du paiement — commande '.$this->commande->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.commande.paiement-echec-client',
            with: [
                ...$this->sharedData(),
                'messageBamboo' => $this->paiement->bamboo_message,
            ],
        );
    }
}
