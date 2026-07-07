<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TypeOffre;

final class QuantiteFormatter
{
    private const UNIT_SYMBOLS = [
        'kg' => 'kg',
        'm3' => 'm³',
        'tonne' => 'tonne',
        'conteneur' => 'conteneur',
        'camion' => 'camion',
    ];

    private const UNIT_PLURALS = [
        'tonne' => 'tonnes',
        'conteneur' => 'conteneurs',
        'camion' => 'camions',
    ];

    public static function format(float|string|null $value, ?TypeOffre $typeOffre = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = (float) $value;
        $formattedNumber = self::formatNumber($numeric, (bool) ($typeOffre?->quantite_entier ?? false));

        if (! $typeOffre?->unite) {
            return $formattedNumber;
        }

        return $formattedNumber.' '.self::formatUnite($typeOffre->unite, $numeric);
    }

    public static function formatPoids(float|string|null $poids): ?string
    {
        if ($poids === null || $poids === '') {
            return null;
        }

        return self::formatNumber((float) $poids, false).' kg';
    }

    public static function colisDisplay(
        float|string|null $poids,
        float|string|null $quantite,
        ?TypeOffre $typeOffre = null,
    ): ?string {
        if ($poids !== null && $poids !== '') {
            return self::formatPoids($poids);
        }

        return self::format($quantite, $typeOffre);
    }

    /**
     * @param  array<string, float|string|null>  $fields
     * @return array<string, float|string|null>
     */
    public static function withLabels(array $fields, ?TypeOffre $typeOffre): array
    {
        $result = [];

        foreach ($fields as $key => $value) {
            $result[$key] = $value;
            $result["{$key}_label"] = self::format($value, $typeOffre);
        }

        return $result;
    }

    private static function formatNumber(float $value, bool $entier): string
    {
        if ($entier) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 3, ',', ' '), '0'), ',');
    }

    private static function formatUnite(string $unite, float $qty): string
    {
        $base = self::UNIT_SYMBOLS[$unite] ?? $unite;

        if (abs($qty) <= 1) {
            return $base;
        }

        return self::UNIT_PLURALS[$base] ?? self::UNIT_PLURALS[$unite] ?? $base;
    }
}
