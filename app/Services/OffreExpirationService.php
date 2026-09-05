<?php

namespace App\Services;

use App\Models\Offre;
use Illuminate\Support\Carbon;

class OffreExpirationService
{
    public const TIMEZONE = 'Africa/Libreville';

    public function desactiverDepartPasses(?Carbon $moment = null): int
    {
        $date = ($moment ?? now(self::TIMEZONE))->toDateString();

        return Offre::query()
            ->where('statut', 'active')
            ->whereNotNull('date_depart')
            ->whereDate('date_depart', '<=', $date)
            ->update(['statut' => 'inactive']);
    }
}
