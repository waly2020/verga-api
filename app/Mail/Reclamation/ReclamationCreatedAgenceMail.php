<?php

namespace App\Mail\Reclamation;

use App\Support\ReclamationMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReclamationCreatedAgenceMail extends ReclamationMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle réclamation — '.$this->reclamation->objet,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reclamation.created-agence',
            with: [
                ...$this->sharedData(),
                'reclamationUrl' => ReclamationMailUrls::agenceShow($this->reclamation),
            ],
        );
    }
}
