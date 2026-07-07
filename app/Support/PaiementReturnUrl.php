<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Paiement;

final class PaiementReturnUrl
{
    public static function for(Paiement $paiement): string
    {
        $code = $paiement->code;

        // Ancre ? pour que Bamboo Pay puisse ajouter &status=...&ref=... sans casser la route.
        return url("/paiement/{$code}/retour").'?ref='.rawurlencode((string) $code);
    }
}
