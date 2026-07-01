<?php

namespace App\Support;

use Carbon\Carbon;

class PeriodeFilter
{
    /**
     * @return array<int, string>
     */
    public static function allowed(): array
    {
        return ['mois', 'mois_dernier', 'trimestre', 'semestre', 'annee', 'tout'];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function range(string $periode): array
    {
        $now = Carbon::now();

        return match ($periode) {
            'mois' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'mois_dernier' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'trimestre' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'semestre' => [$now->copy()->subMonths(6)->startOfDay(), $now->copy()->endOfDay()],
            'annee' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [Carbon::createFromDate(2020, 1, 1)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
