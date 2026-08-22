<?php

namespace App\Mail\Reversement;

use App\Support\ReversementMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

class ReversementEffectueAgenceMail extends ReversementMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reversement effectué — '.$this->reversement->periode,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reversement.effectue-agence',
            with: [
                ...$this->sharedData(),
                'effectueLe' => $this->reversement->effectue_le
                    ? Carbon::parse($this->reversement->effectue_le)->format('d/m/Y à H:i')
                    : null,
                'reversementsUrl' => ReversementMailUrls::agenceIndex(),
            ],
        );
    }
}
