<?php

namespace App\Support;

use App\Models\PaiementPublicite;

final class PublicitePaiementReturnUrl
{
    public static function for(PaiementPublicite $paiement): string
    {
        $code = $paiement->code;

        return url("/publicite-paiement/{$code}/retour").'?ref='.rawurlencode((string) $code);
    }
}
