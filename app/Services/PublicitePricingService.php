<?php

namespace App\Services;

use App\Models\ConfigurationPublicite;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PublicitePricingService
{
    /**
     * @return array{
     *     nombre_jours: int,
     *     prix_par_jour: int,
     *     montant_sous_total: int,
     *     montant_frais: int,
     *     montant_total: int,
     *     type_frais: string,
     *     valeur_frais: int
     * }
     */
    public function calculate(CarbonInterface $debut, CarbonInterface $fin): array
    {
        $config = ConfigurationPublicite::query()->where('actif', true)->first();

        if (! $config) {
            throw ValidationException::withMessages([
                'publicite' => ['Le tarif des publicités n\'est pas encore configuré.'],
            ]);
        }

        $jours = $this->nombreJours($debut, $fin);
        $prixParJour = (int) $config->prix_par_jour;
        $sousTotal = $jours * $prixParJour;
        $frais = $config->calculerFrais($sousTotal);

        return [
            'nombre_jours' => $jours,
            'prix_par_jour' => $prixParJour,
            'montant_sous_total' => $sousTotal,
            'montant_frais' => $frais,
            'montant_total' => $sousTotal + $frais,
            'type_frais' => $config->type_frais,
            'valeur_frais' => (int) $config->valeur_frais,
        ];
    }

    public function nombreJours(CarbonInterface $debut, CarbonInterface $fin): int
    {
        $start = Carbon::parse($debut)->startOfDay();
        $end = Carbon::parse($fin)->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }
}
