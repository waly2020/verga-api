<?php

namespace App\Mail\Commande;

use App\Support\CommandeMailUrls;
use App\Support\MailLabels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaiementCommandeValideClientMail extends CommandeMail
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement confirmé — commande '.$this->commande->code,
        );
    }

    public function content(): Content
    {
        $this->commande->refresh();

        return new Content(
            markdown: 'mail.commande.paiement-valide-client',
            with: [
                ...$this->sharedData(),
                'commandeStatut' => MailLabels::commandeStatut($this->commande->statut),
                'commandeUrl' => CommandeMailUrls::clientCommande($this->commande),
            ],
        );
    }
}
