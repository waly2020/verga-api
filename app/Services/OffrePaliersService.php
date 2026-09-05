<?php

namespace App\Services;

use App\Models\Offre;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class OffrePaliersService
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(bool $sometimes = false): array
    {
        $presence = $sometimes ? ['sometimes', 'nullable'] : ['nullable'];

        return [
            'paliers' => [...$presence, 'array', 'min:2'],
            'paliers.*.min' => ['required', 'numeric', 'min:0.001'],
            'paliers.*.max' => ['nullable', 'numeric'],
            'paliers.*.prix' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paliers.min' => 'Une offre à paliers doit contenir au moins deux intervalles.',
            'paliers.*.min.required' => 'La quantité minimale du palier est obligatoire.',
            'paliers.*.min.min' => 'La quantité minimale du palier doit être supérieure à zéro.',
            'paliers.*.prix.required' => 'Le prix du palier est obligatoire.',
            'paliers.*.prix.min' => 'Le prix du palier ne peut pas être négatif.',
        ];
    }

    /**
     * @return list<array{min: mixed, max: mixed, prix: mixed}>|null
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

            $max = $palier['max'] ?? null;

            $normalized[] = [
                'min' => $palier['min'] ?? null,
                'max' => $max === '' ? null : $max,
                'prix' => $palier['prix'] ?? null,
            ];
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $paliers
     * @return list<array{min: float, max: float|null, prix: float}>
     */
    public function normalizeStored(array $paliers): array
    {
        return collect($paliers)
            ->map(fn (array $palier): array => [
                'min' => round((float) $palier['min'], 3),
                'max' => isset($palier['max']) && $palier['max'] !== null && $palier['max'] !== ''
                    ? round((float) $palier['max'], 3)
                    : null,
                'prix' => round((float) $palier['prix'], 2),
            ])
            ->sortBy('min')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $paliers
     */
    public function validateBusinessRules(?array $paliers, Validator $validator, bool $destinationForcePrix): void
    {
        if ($destinationForcePrix && $paliers !== null && $paliers !== []) {
            $validator->errors()->add(
                'paliers',
                'Les paliers sont interdits lorsque la destination impose un prix.',
            );

            return;
        }

        if ($paliers === null || $paliers === []) {
            return;
        }

        $sorted = $this->normalizeStored($paliers);
        $lastIndex = count($sorted) - 1;

        foreach ($sorted as $index => $palier) {
            if ($palier['max'] !== null && $palier['max'] < $palier['min'] - 0.0001) {
                $validator->errors()->add(
                    "paliers.{$index}.max",
                    'La quantité maximale doit être supérieure ou égale à la quantité minimale.',
                );
            }

            if ($index === $lastIndex && $palier['max'] !== null) {
                $validator->errors()->add(
                    "paliers.{$index}.max",
                    'Le dernier palier doit aller jusqu\'à N (quantité max vide).',
                );
            }

            if ($index < $lastIndex && $palier['max'] === null) {
                $validator->errors()->add(
                    "paliers.{$index}.max",
                    'Seuls le dernier palier peut être ouvert (jusqu\'à N).',
                );
            }

            if ($index === 0) {
                continue;
            }

            $previous = $sorted[$index - 1];

            if ($previous['max'] !== null && $palier['min'] <= $previous['max'] + 0.0001) {
                $validator->errors()->add(
                    "paliers.{$index}.min",
                    'Les intervalles de paliers se chevauchent.',
                );
            }
        }
    }

    public function hasPaliers(Offre $offre): bool
    {
        return is_array($offre->paliers) && $offre->paliers !== [];
    }

    public function unitPrice(Offre $offre, float $quantite): float
    {
        if (! $this->hasPaliers($offre)) {
            return (float) $offre->prix;
        }

        foreach ($offre->paliers as $palier) {
            $min = (float) ($palier['min'] ?? 0);
            $max = $palier['max'] ?? null;
            $max = $max === null || $max === '' ? null : (float) $max;

            if ($quantite + 0.0001 >= $min && ($max === null || $quantite <= $max + 0.0001)) {
                return (float) $palier['prix'];
            }
        }

        throw ValidationException::withMessages([
            'quantite' => ['Aucun palier de prix ne correspond à cette quantité.'],
        ]);
    }
}
