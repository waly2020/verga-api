<?php

namespace App\Services;

use App\Models\Offre;
use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Support\Carbon;

class OffreExpirationService
{
    public const TIMEZONE = 'Africa/Libreville';

    public function desactiverDepartPasses(?Carbon $moment = null): int
    {
        $date = ($moment ?? now(self::TIMEZONE))->toDateString();

        $offres = Offre::query()
            ->where('statut', 'active')
            ->whereNotNull('date_depart')
            ->whereDate('date_depart', '<=', $date)
            ->get(['id', 'titre', 'date_depart', 'agence_id']);

        $count = 0;

        if ($offres->isNotEmpty()) {
            $count = Offre::query()
                ->whereIn('id', $offres->modelKeys())
                ->update(['statut' => 'inactive']);
        }

        app(AuditLogService::class)->record(
            AuditAction::JobOffresExpire,
            [
                'date_reference' => $date,
                'count' => $count,
                'offres' => $offres->map(fn (Offre $offre) => [
                    'id' => $offre->id,
                    'titre' => $offre->titre,
                    'date_depart' => $offre->date_depart?->toDateString(),
                    'agence_id' => $offre->agence_id,
                ])->all(),
            ],
            actor: app(AuditLogService::class)->systeme(),
        );

        return $count;
    }
}
