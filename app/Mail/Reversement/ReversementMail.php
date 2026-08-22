<?php

namespace App\Mail\Reversement;

use App\Mail\Concerns\QueuesAfterCommit;
use App\Models\Reversement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

abstract class ReversementMail extends Mailable implements ShouldQueue
{
    use Queueable, QueuesAfterCommit, SerializesModels;

    public function __construct(
        public Reversement $reversement,
    ) {
        $this->configureQueuesAfterCommit();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedData(): array
    {
        $this->reversement->loadMissing(['agence.proprietaire']);

        return [
            'gerantName' => $this->reversement->agence?->proprietaire?->name
                ?? $this->reversement->agence?->nom
                ?? 'Gérant',
            'agenceName' => $this->reversement->agence?->nom,
            'montant' => $this->formatMontant((float) $this->reversement->montant),
            'periode' => $this->reversement->periode,
            'statut' => $this->reversement->statut,
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
