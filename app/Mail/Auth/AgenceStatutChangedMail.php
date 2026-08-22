<?php

namespace App\Mail\Auth;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Agence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgenceStatutChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public Agence $agence,
        public string $nouveauStatut,
    ) {
        $this->configureQueuesAfterCommit();
    }

    public function envelope(): Envelope
    {
        $subject = $this->nouveauStatut === 'bloqué'
            ? 'Compte agence suspendu — '.config('app.name')
            : 'Compte agence réactivé — '.config('app.name');

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.agence-statut-changed',
            with: [
                'agenceName' => $this->agence->nom,
                'nouveauStatut' => $this->nouveauStatut,
                'isBlocked' => $this->nouveauStatut === 'bloqué',
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
