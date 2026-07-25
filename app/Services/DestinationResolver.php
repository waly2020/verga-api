<?php

namespace App\Services;

use App\Models\Destination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DestinationResolver
{
    public function normalizeLabel(string $value): string
    {
        return Str::of($value)
            ->squish()
            ->lower()
            ->toString();
    }

    /**
     * @param  array{
     *     depart: string,
     *     arrivee: string,
     *     montant?: float|string|null,
     *     commission_pourcentage?: float|string|null,
     *     appliquer_configuration?: bool,
     *     actif?: bool
     * }  $attributes
     */
    public function createForAdmin(array $attributes): Destination
    {
        $depart = $this->normalizeLabel($attributes['depart']);
        $arrivee = $this->normalizeLabel($attributes['arrivee']);

        if ($depart === '' || $arrivee === '') {
            throw ValidationException::withMessages([
                'depart' => ['Le départ et l\'arrivée sont obligatoires.'],
            ]);
        }

        if (Destination::query()->where('depart', $depart)->where('arrivee', $arrivee)->exists()) {
            throw ValidationException::withMessages([
                'depart' => ['Cette destination existe déjà.'],
            ]);
        }

        $appliquer = (bool) ($attributes['appliquer_configuration'] ?? false);

        return Destination::create([
            'depart' => $depart,
            'arrivee' => $arrivee,
            'appliquer_configuration' => $appliquer,
            'montant' => $appliquer ? $attributes['montant'] : null,
            'commission_pourcentage' => $appliquer ? $attributes['commission_pourcentage'] : null,
            'actif' => (bool) ($attributes['actif'] ?? true),
        ]);
    }

    /**
     * Trouve ou crée une destination (sans config spéciale), puis l'attache à l'agence.
     */
    public function findOrCreateAndAttach(string $agenceId, string $depart, string $arrivee): Destination
    {
        $depart = $this->normalizeLabel($depart);
        $arrivee = $this->normalizeLabel($arrivee);

        if ($depart === '' || $arrivee === '') {
            throw ValidationException::withMessages([
                'depart' => ['Le départ et l\'arrivée sont obligatoires.'],
            ]);
        }

        return DB::transaction(function () use ($agenceId, $depart, $arrivee) {
            $destination = Destination::query()
                ->where('depart', $depart)
                ->where('arrivee', $arrivee)
                ->lockForUpdate()
                ->first();

            if (! $destination) {
                $destination = Destination::create([
                    'depart' => $depart,
                    'arrivee' => $arrivee,
                    'appliquer_configuration' => false,
                    'montant' => null,
                    'commission_pourcentage' => null,
                    'actif' => true,
                ]);
            } elseif (! $destination->actif) {
                throw ValidationException::withMessages([
                    'depart' => ['Cette destination existe mais est inactive. Demandez à un administrateur de la réactiver.'],
                ]);
            }

            $destination->agences()->syncWithoutDetaching([$agenceId]);

            return $destination->fresh();
        });
    }

    public function attachToAgence(Destination $destination, string $agenceId): Destination
    {
        $destination->agences()->syncWithoutDetaching([$agenceId]);

        return $destination;
    }

    /**
     * Destination active utilisable par l'agence : rattache automatiquement au besoin.
     */
    public function ensureAvailableForAgence(Destination $destination, string $agenceId): void
    {
        if (! $destination->actif) {
            throw ValidationException::withMessages([
                'destination_id' => ['Cette destination est inactive.'],
            ]);
        }

        $this->attachToAgence($destination, $agenceId);
    }
}
