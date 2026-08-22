<?php

namespace App\Mail\Commande;

use App\Support\CommandeMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CommandeCreatedAgenceMail extends CommandeMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle commande '.$this->commande->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.commande.created-agence',
            with: [
                ...$this->sharedData(),
                'commandeUrl' => CommandeMailUrls::agenceCommande($this->commande),
            ],
        );
    }
}
