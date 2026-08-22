<?php

namespace App\Mail\Colis;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Colis;
use App\Support\ColisMailUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ColisStatutChangedClientMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public Colis $colis,
        public string $nouveauStatut,
    ) {
        $this->configureQueuesAfterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForStatut($this->nouveauStatut),
        );
    }

    public function content(): Content
    {
        $this->colis->loadMissing(['commande.offre', 'agence']);
        $commande = $this->colis->commande;

        return new Content(
            markdown: 'mail.colis.statut-changed-client',
            with: [
                'clientName' => $commande
                    ? trim("{$commande->prenom} {$commande->nom}")
                    : 'Madame, Monsieur',
                'colisReference' => $this->colis->reference,
                'commandeCode' => $commande?->code,
                'offreTitre' => $commande?->offre?->titre,
                'agenceName' => $this->colis->agence?->nom,
                'statutLabel' => $this->labelForStatut($this->nouveauStatut),
                'messageStatut' => $this->messageForStatut($this->nouveauStatut),
                'colisUrl' => ColisMailUrls::clientColis($this->colis),
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

    private function subjectForStatut(string $statut): string
    {
        return match ($statut) {
            'déposé' => 'Colis déposé en agence — '.$this->colis->reference,
            'en_transit' => 'Colis en transit — '.$this->colis->reference,
            'arrivé' => 'Colis arrivé à destination — '.$this->colis->reference,
            'récupéré' => 'Colis récupéré — '.$this->colis->reference,
            default => 'Mise à jour colis — '.$this->colis->reference,
        };
    }

    private function labelForStatut(string $statut): string
    {
        return match ($statut) {
            'déposé' => 'déposé en agence',
            'en_transit' => 'en transit',
            'arrivé' => 'arrivé à destination',
            'récupéré' => 'récupéré',
            default => $statut,
        };
    }

    private function messageForStatut(string $statut): string
    {
        return match ($statut) {
            'déposé' => 'Votre colis a été déposé auprès de l\'agence et sera bientôt expédié.',
            'en_transit' => 'Votre colis est en cours d\'acheminement vers sa destination.',
            'arrivé' => 'Votre colis est arrivé. Vous pouvez le retirer selon les modalités de l\'agence.',
            'récupéré' => 'Votre colis a été marqué comme récupéré. Merci de votre confiance.',
            default => 'Le statut de votre colis a été mis à jour.',
        };
    }
}
