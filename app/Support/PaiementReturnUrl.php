<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Paiement;

final class PaiementReturnUrl
{
    public static function for(Paiement $paiement): string
    {
        return url("/paiement/{$paiement->code}/retour");
    }
}
