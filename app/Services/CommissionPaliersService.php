<?php

namespace App\Services;

use App\Models\CommissionPalier;
use App\Models\ConfigurationCommission;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class CommissionPaliersService
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'paliers' => ['required', 'array', 'min:1'],
            'paliers.*.montant_min' => ['required', 'numeric', 'min:0'],
            'paliers.*.montant_max' => ['nullable', 'numeric'],
            'paliers.*.frais' => ['required', 'numeric', 'min:0'],
            'paliers.*.libelle' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paliers.required' => 'La grille tarifaire doit contenir au moins une tranche.',
            'paliers.min' => 'La grille tarifaire doit contenir au moins une tranche.',
            'paliers.*.montant_min.required' => 'Le montant minimal de la tranche est obligatoire.',
            'paliers.*.montant_min.min' => 'Le montant minimal ne peut pas être négatif.',
            'paliers.*.frais.required' => 'Les frais de la tranche sont obligatoires.',
            'paliers.*.frais.min' => 'Les frais ne peuvent pas être négatifs.',
        ];
    }

    /**
     * @return list<array{montant_min: mixed, montant_max: mixed, frais: mixed, libelle: mixed}>|null
     */
    public function prepareInput(mixed $paliers): ?array
    {
        if ($paliers === null || $paliers === '' || $paliers === []) {
            return null;
        }

        if (! is_array($paliers)) {
            return null;
        }

        $normalized = [];

        foreach ($paliers as $palier) {
            if (! is_array($palier)) {
                continue;
            }

            $max = $palier['montant_max'] ?? null;
            $libelle = $palier['libelle'] ?? null;

            $normalized[] = [
                'montant_min' => $palier['montant_min'] ?? null,
                'montant_max' => $max === '' ? null : $max,
                'frais' => $palier['frais'] ?? null,
                'libelle' => is_string($libelle) ? trim($libelle) : $libelle,
            ];
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $paliers
     * @return list<array{montant_min: float, montant_max: float|null, frais: float, libelle: string|null}>
     */
    public function normalizeStored(array $paliers): array
    {
        return collect($paliers)
            ->map(fn (array $palier): array => [
                'montant_min' => round((float) $palier['montant_min'], 2),
                'montant_max' => isset($palier['montant_max']) && $palier['montant_max'] !== null && $palier['montant_max'] !== ''
                    ? round((float) $palier['montant_max'], 2)
                    : null,
                'frais' => round((float) $palier['frais'], 2),
                'libelle' => filled($palier['libelle'] ?? null) ? (string) $palier['libelle'] : null,
            ])
            ->sortBy('montant_min')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $paliers
     */
    public function validateBusinessRules(?array $paliers, Validator $validator): void
    {
        if ($paliers === null || $paliers === []) {
            $validator->errors()->add('paliers', 'La grille tarifaire doit contenir au moins une tranche.');

            return;
        }

        $sorted = $this->normalizeStored($paliers);
        $lastIndex = count($sorted) - 1;

        foreach ($sorted as $index => $palier) {
            if ($palier['montant_max'] !== null && $palier['montant_max'] < $palier['montant_min'] - 0.001) {
                $validator->errors()->add(
                    "paliers.{$index}.montant_max",
                    'Le montant maximal doit être supérieur ou égal au montant minimal.',
                );
            }

            if ($index === $lastIndex && $palier['montant_max'] !== null) {
                $validator->errors()->add(
                    "paliers.{$index}.montant_max",
                    'La dernière tranche doit être ouverte (montant max vide).',
                );
            }

            if ($index < $lastIndex && $palier['montant_max'] === null) {
                $validator->errors()->add(
                    "paliers.{$index}.montant_max",
                    'Seule la dernière tranche peut être ouverte.',
                );
            }

            if ($index === 0) {
                continue;
            }

            $previous = $sorted[$index - 1];

            if ($previous['montant_max'] !== null && $palier['montant_min'] <= $previous['montant_max'] + 0.001) {
                $validator->errors()->add(
                    "paliers.{$index}.montant_min",
                    'Les tranches de commission se chevauchent.',
                );
            }
        }
    }

    /**
     * @return array{montant: float, libelle: string|null}
     */
    public function resolve(ConfigurationCommission $config, float $montantSousTotal): array
    {
        if (! $config->actif) {
            return ['montant' => 0.0, 'libelle' => null];
        }

        if (! $config->estGrille()) {
            return [
                'montant' => $config->calculerMontant($montantSousTotal),
                'libelle' => $config->libelle,
            ];
        }

        $palier = $this->matchingPalier($config, $montantSousTotal);

        if ($palier === null) {
            throw ValidationException::withMessages([
                'montant' => ['Aucune tranche de commission ne correspond à ce montant.'],
            ]);
        }

        return [
            'montant' => round((float) $palier->frais, 2),
            'libelle' => $palier->libelle,
        ];
    }

    private function matchingPalier(ConfigurationCommission $config, float $montantSousTotal): ?CommissionPalier
    {
        /** @var Collection<int, CommissionPalier> $paliers */
        $paliers = $config->relationLoaded('paliers')
            ? $config->paliers
            : $config->paliers()->get();

        return $paliers->first(
            fn (CommissionPalier $palier): bool => $palier->couvre($montantSousTotal)
        );
    }
}
