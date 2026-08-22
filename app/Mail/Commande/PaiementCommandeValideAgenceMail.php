<?php

namespace App\Mail\Commande;

use App\Support\CommandeMailUrls;
use App\Support\MailLabels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaiementCommandeValideAgenceMail extends CommandeMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement reçu — commande '.$this->commande->code,
        );
    }

    public function content(): Content
    {
        $this->commande->refresh();

        return new Content(
            markdown: 'mail.commande.paiement-valide-agence',
            with: [
                ...$this->sharedData(),
                'commandeStatut' => MailLabels::commandeStatut($this->commande->statut),
                'commandeUrl' => CommandeMailUrls::agenceCommande($this->commande),
            ],
        );
    }
}
