<?php

namespace App\Mail\Auth;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\User;
use App\Support\EmailVerificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollaborateurWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public User $user,
    ) {
        $this->configureQueuesAfterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte administrateur — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.collaborateur-welcome',
            with: [
                'userName' => $this->user->name,
                'roleLabel' => $this->user->role === 'admin' ? 'administrateur' : 'collaborateur',
                'loginUrl' => url('/login'),
                'verificationUrl' => $this->user->hasVerifiedEmail()
                    ? null
                    : EmailVerificationUrl::forUser($this->user),
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
