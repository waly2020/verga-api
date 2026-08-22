<?php

namespace App\Mail\Commande;

use App\Support\CommandeMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CommandeCreatedClientMail extends CommandeMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Commande '.$this->commande->code.' — paiement en attente',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.commande.created-client',
            with: [
                ...$this->sharedData(),
                'commandeUrl' => CommandeMailUrls::clientCommande($this->commande),
            ],
        );
    }
}
