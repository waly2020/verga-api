<?php

namespace App\Mail\Reversement;

use App\Support\ReversementMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReversementCreatedAgenceMail extends ReversementMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reversement en attente — '.$this->reversement->periode,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reversement.created-agence',
            with: [
                ...$this->sharedData(),
                'reversementsUrl' => ReversementMailUrls::agenceIndex(),
            ],
        );
    }
}
