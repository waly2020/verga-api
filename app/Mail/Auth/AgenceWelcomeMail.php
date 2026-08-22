<?php

namespace App\Mail\Auth;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Agence;
use App\Models\AgenceUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgenceWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public AgenceUser $agenceUser,
        public Agence $agence,
    ) {
        $this->configureQueuesAfterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue — espace agence '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.agence-welcome',
            with: [
                'userName' => $this->agenceUser->name,
                'agenceName' => $this->agence->nom,
                'appUrl' => config('verga.frontend.agence_url'),
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
