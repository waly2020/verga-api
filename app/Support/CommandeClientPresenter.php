<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Commande;

final class CommandeClientPresenter
{
    /**
     * @return array{id: string|null, nom: string|null, prenom: string|null, email: string|null, telephone: string|null}|null
     */
    public static function for(Commande $commande): ?array
    {
        if ($commande->client) {
            return [
                'id' => $commande->client->id,
                'nom' => $commande->client->nom,
                'prenom' => $commande->client->prenom,
                'email' => $commande->client->email,
                'telephone' => $commande->client->telephone,
            ];
        }

        if ($commande->nom || $commande->prenom || $commande->telephone) {
            return [
                'id' => null,
                'nom' => $commande->nom,
                'prenom' => $commande->prenom,
                'email' => null,
                'telephone' => $commande->telephone,
            ];
        }

        return null;
    }
}
