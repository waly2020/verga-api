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

class AgenceCollaborateurWelcomeMail extends Mailable implements ShouldQueue
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
            subject: 'Accès à l\'équipe — '.$this->agence->nom,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.agence-collaborateur-welcome',
            with: [
                'userName' => $this->agenceUser->name,
                'agenceName' => $this->agence->nom,
                'roleName' => $this->agenceUser->role?->nom ?? 'collaborateur',
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
