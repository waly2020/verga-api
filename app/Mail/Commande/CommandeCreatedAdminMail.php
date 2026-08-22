<?php

namespace App\Mail\Commande;

use App\Support\CommandeMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CommandeCreatedAdminMail extends CommandeMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Admin] Nouvelle commande '.$this->commande->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.commande.created-admin',
            with: [
                ...$this->sharedData(),
                'commandeUrl' => CommandeMailUrls::adminShow($this->commande),
            ],
        );
    }
}
