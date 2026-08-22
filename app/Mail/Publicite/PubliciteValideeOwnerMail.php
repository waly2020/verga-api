<?php

namespace App\Mail\Publicite;

use App\Support\PubliciteMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PubliciteValideeOwnerMail extends PubliciteMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Publicité validée — '.$this->publicite->titre,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.publicite.validee-owner',
            with: [
                ...$this->sharedData(),
                'publiciteUrl' => PubliciteMailUrls::ownerShow($this->publicite),
            ],
        );
    }
}
