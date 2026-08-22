<?php

namespace App\Services;

use App\Mail\Commande\CommandeCreatedAdminMail;
use App\Mail\Commande\CommandeCreatedAgenceMail;
use App\Mail\Commande\CommandeCreatedClientMail;
use App\Mail\Commande\PaiementCommandeEchecClientMail;
use App\Mail\Commande\PaiementCommandeValideAgenceMail;
use App\Mail\Commande\PaiementCommandeValideClientMail;
use App\Models\Commande;
use App\Models\Paiement;

class CommandeMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function notifyCommandeCreated(Commande $commande, Paiement $paiement): void
    {
        $commande->loadMissing(['agence', 'offre', 'client.user']);

        $clientEmail = $this->clientEmail($commande);

        if ($clientEmail) {
            $this->mail->queue(
                new CommandeCreatedClientMail($commande, $paiement),
                $clientEmail,
            );
        }

        if ($commande->agence?->email) {
            $this->mail->queue(
                new CommandeCreatedAgenceMail($commande, $paiement),
                $commande->agence->email,
            );
        }

        $adminAddress = config('verga.mail.admin_address');

        if (is_string($adminAddress) && $adminAddress !== '') {
            $this->mail->queue(
                new CommandeCreatedAdminMail($commande, $paiement),
                $adminAddress,
            );
        }
    }

    public function notifyPaiementValide(Paiement $paiement): void
    {
        $paiement->loadMissing(['commande.agence', 'commande.offre', 'commande.client.user']);
        $commande = $paiement->commande;

        if (! $commande) {
            return;
        }

        $clientEmail = $this->clientEmail($commande);

        if ($clientEmail) {
            $this->mail->queue(
                new PaiementCommandeValideClientMail($commande, $paiement),
                $clientEmail,
            );
        }

        if ($commande->agence?->email) {
            $this->mail->queue(
                new PaiementCommandeValideAgenceMail($commande, $paiement),
                $commande->agence->email,
            );
        }
    }

    public function notifyPaiementEchec(Paiement $paiement): void
    {
        $paiement->loadMissing(['commande.agence', 'commande.offre', 'commande.client.user']);
        $commande = $paiement->commande;

        if (! $commande) {
            return;
        }

        $clientEmail = $this->clientEmail($commande);

        if (! $clientEmail) {
            return;
        }

        $this->mail->queue(
            new PaiementCommandeEchecClientMail($commande, $paiement),
            $clientEmail,
        );
    }

    private function clientEmail(Commande $commande): ?string
    {
        $email = $commande->client?->email ?? $commande->client?->user?->email;

        if (! is_string($email) || $email === '') {
            return null;
        }

        return $email;
    }
}
