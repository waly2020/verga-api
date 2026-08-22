<?php

namespace App\Mail\Commande;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Commande;
use App\Models\Paiement;
use App\Support\CommandeMailUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

abstract class CommandeMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public Commande $commande,
        public Paiement $paiement,
    ) {
        $this->configureQueuesAfterCommit();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedData(): array
    {
        $this->commande->loadMissing(['agence', 'offre']);

        return [
            'commandeCode' => $this->commande->code,
            'clientName' => trim("{$this->commande->prenom} {$this->commande->nom}"),
            'agenceName' => $this->commande->agence?->nom,
            'offreTitre' => $this->commande->offre?->titre,
            'montantTotal' => $this->formatMontant((float) $this->paiement->montant),
            'paiementCode' => $this->paiement->code,
            'retourUrl' => CommandeMailUrls::paiementRetour($this->paiement),
        ];
    }

    protected function formatMontant(float $montant): string
    {
        return number_format($montant, 0, ',', ' ').' FCFA';
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
