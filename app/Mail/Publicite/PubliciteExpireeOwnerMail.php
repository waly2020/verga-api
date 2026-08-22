<?php

namespace App\Mail\Publicite;

use App\Support\PubliciteMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PubliciteExpireeOwnerMail extends PubliciteMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Publicité expirée — '.$this->publicite->titre,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.publicite.expiree-owner',
            with: [
                ...$this->sharedData(),
                'publiciteUrl' => PubliciteMailUrls::ownerShow($this->publicite),
            ],
        );
    }
}
