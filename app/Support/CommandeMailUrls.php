<?php

namespace App\Support;

use App\Models\Commande;
use App\Models\Paiement;

class CommandeMailUrls
{
    public static function adminShow(Commande $commande): string
    {
        return route('admin.commandes.show', $commande);
    }

    public static function clientCommande(Commande $commande): string
    {
        $base = rtrim((string) config('verga.frontend.client_url'), '/');

        return "{$base}/commandes/{$commande->id}";
    }

    public static function agenceCommande(Commande $commande): string
    {
        $base = rtrim((string) config('verga.frontend.agence_url'), '/');

        return "{$base}/commandes/{$commande->id}";
    }

    public static function paiementRetour(Paiement $paiement): string
    {
        return PaiementReturnUrl::for($paiement);
    }
}
