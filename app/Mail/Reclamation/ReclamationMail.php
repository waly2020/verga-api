<?php

namespace App\Mail\Reclamation;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

abstract class ReclamationMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public Reclamation $reclamation,
    ) {
        $this->configureQueuesAfterCommit();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedData(): array
    {
        $this->reclamation->loadMissing(['agence', 'commande']);

        return [
            'clientName' => trim("{$this->reclamation->prenom} {$this->reclamation->nom}"),
            'objet' => $this->reclamation->objet,
            'description' => $this->reclamation->description,
            'agenceName' => $this->reclamation->agence?->nom,
            'commandeCode' => $this->reclamation->commande?->code,
            'statut' => $this->reclamation->statut,
        ];
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
