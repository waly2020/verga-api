<?php

namespace App\Mail\Reclamation;

use App\Support\ReclamationMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReclamationCreatedAdminMail extends ReclamationMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Admin] Nouvelle réclamation — '.$this->reclamation->objet,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reclamation.created-admin',
            with: [
                ...$this->sharedData(),
                'reclamationUrl' => ReclamationMailUrls::adminShow($this->reclamation),
            ],
        );
    }
}
