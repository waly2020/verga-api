<?php

namespace App\Mail\Publicite;

use App\Support\PubliciteMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PubliciteRetireeOwnerMail extends PubliciteMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Publicité retirée — '.$this->publicite->titre,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.publicite.retiree-owner',
            with: [
                ...$this->sharedData(),
                'publiciteUrl' => PubliciteMailUrls::ownerShow($this->publicite),
            ],
        );
    }
}
