<?php

namespace App\Support;

use App\Models\PaiementPublicite;
use App\Models\Publicite;

class PubliciteMailUrls
{
    public static function adminModeration(): string
    {
        return route('admin.publicites.index', ['statut' => 'en_attente']);
    }

    public static function ownerShow(Publicite $publicite): string
    {
        if ($publicite->agence_id) {
            $base = rtrim((string) config('verga.frontend.agence_url'), '/');

            return "{$base}/publicites/{$publicite->id}";
        }

        $base = rtrim((string) config('verga.frontend.client_url'), '/');

        return "{$base}/publicites/{$publicite->id}";
    }

    public static function paiementRetour(PaiementPublicite $paiement): string
    {
        return PublicitePaiementReturnUrl::for($paiement);
    }
}
