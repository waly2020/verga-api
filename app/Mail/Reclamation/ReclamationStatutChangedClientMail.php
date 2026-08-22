<?php

namespace App\Mail\Reclamation;

use App\Models\Reclamation;
use App\Support\ReclamationMailUrls;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReclamationStatutChangedClientMail extends ReclamationMail
{
    public function __construct(
        Reclamation $reclamation,
        public string $nouveauStatut,
    ) {
        parent::__construct($reclamation);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réclamation '.$this->labelForStatut($this->nouveauStatut).' — '.$this->reclamation->objet,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reclamation.statut-changed-client',
            with: [
                ...$this->sharedData(),
                'nouveauStatut' => $this->nouveauStatut,
                'statutLabel' => $this->labelForStatut($this->nouveauStatut),
                'messageStatut' => $this->messageForStatut($this->nouveauStatut),
                'reclamationUrl' => ReclamationMailUrls::clientShow($this->reclamation),
            ],
        );
    }

    private function labelForStatut(string $statut): string
    {
        return match ($statut) {
            'en_cours' => 'prise en charge',
            'résolue' => 'résolue',
            'fermée' => 'fermée',
            default => $statut,
        };
    }

    private function messageForStatut(string $statut): string
    {
        return match ($statut) {
            'en_cours' => 'Votre réclamation est prise en charge par notre équipe.',
            'résolue' => 'Votre réclamation a été traitée et marquée comme résolue.',
            'fermée' => 'Votre réclamation a été fermée.',
            default => 'Le statut de votre réclamation a été mis à jour.',
        };
    }
}
