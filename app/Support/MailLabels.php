<?php

namespace App\Support;

class MailLabels
{
    public static function commandeStatut(string $statut): string
    {
        return match ($statut) {
            'en_attente' => 'En attente',
            'réservée' => 'Réservée',
            'confirmée' => 'Confirmée',
            'annulée' => 'Annulée',
            default => $statut,
        };
    }

    public static function paiementStatut(string $statut): string
    {
        return match ($statut) {
            'en_attente' => 'En attente',
            'validé' => 'Validé',
            'échec' => 'Échec',
            'annulé' => 'Annulé',
            default => $statut,
        };
    }
}
