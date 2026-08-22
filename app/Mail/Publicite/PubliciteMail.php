<?php

namespace App\Mail\Publicite;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Publicite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

abstract class PubliciteMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public Publicite $publicite,
    ) {
        $this->configureQueuesAfterCommit();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedData(): array
    {
        $this->publicite->loadMissing(['agence', 'client.user', 'offre']);

        return [
            'ownerName' => $this->ownerName(),
            'titre' => $this->publicite->titre,
            'description' => $this->publicite->description,
            'dateDebut' => Carbon::parse($this->publicite->date_debut)->format('d/m/Y'),
            'dateFin' => Carbon::parse($this->publicite->date_fin)->format('d/m/Y'),
            'nombreJours' => $this->publicite->nombre_jours,
            'statut' => $this->publicite->statut,
            'statutPaiement' => $this->publicite->statut_paiement,
            'agenceName' => $this->publicite->agence?->nom,
            'offreTitre' => $this->publicite->offre?->titre,
            'motifRefus' => $this->publicite->motif_refus,
        ];
    }

    protected function ownerName(): string
    {
        if ($this->publicite->agence) {
            return $this->publicite->agence->nom;
        }

        if ($this->publicite->client) {
            return trim("{$this->publicite->client->prenom} {$this->publicite->client->nom}");
        }

        return config('app.name');
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
