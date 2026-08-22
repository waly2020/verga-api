<?php

namespace App\Mail\Publicite;

use App\Support\PubliciteMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PubliciteSubmittedAdminMail extends PubliciteMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Admin] Publicité à modérer — '.$this->publicite->titre,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.publicite.submitted-admin',
            with: [
                ...$this->sharedData(),
                'moderationUrl' => PubliciteMailUrls::adminModeration(),
            ],
        );
    }
}
