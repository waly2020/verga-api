<?php

namespace Tests\Support;

use App\Models\Agence;
use App\Models\Destination;
use App\Models\Offre;
use App\Models\Ville;
use Illuminate\Support\Str;

trait CreatesTestDestinations
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createVille(array $attributes = []): Ville
    {
        static $counter = 0;
        $counter++;

        return Ville::query()->create(array_merge([
            'pays' => 'Gabon',
            'ville' => "Ville{$counter}",
            'code' => 'V'.$counter,
            'actif' => true,
        ], $attributes));
    }

    protected function villeFromLabel(string $ville, bool $actif = true): Ville
    {
        $ville = Str::of($ville)->squish()->toString();
        $normalized = Str::lower($ville);

        $existing = Ville::query()
            ->whereRaw('lower(ville) = ?', [$normalized])
            ->first();

        if ($existing) {
            return $existing;
        }

        $codeBase = Str::upper(Str::substr(Str::slug($ville, ''), 0, 24)) ?: 'LOC';
        $code = $codeBase;
        $suffix = 1;

        while (Ville::query()->where('code', $code)->exists()) {
            $code = Str::substr($codeBase, 0, 28).$suffix;
            $suffix++;
        }

        return Ville::query()->create([
            'pays' => Ville::reusePays('Test'),
            'ville' => $ville,
            'code' => $code,
            'actif' => $actif,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createDestination(array $attributes = [], ?Agence $agence = null): Destination
    {
        static $counter = 0;
        $counter++;

        $depart = $attributes['depart'] ?? null;
        $arrivee = $attributes['arrivee'] ?? null;
        unset($attributes['depart'], $attributes['arrivee']);

        if (! isset($attributes['ville_depart_id'])) {
            $attributes['ville_depart_id'] = $this->villeFromLabel(
                is_string($depart) && $depart !== '' ? $depart : "depart{$counter}"
            )->id;
        }

        if (! isset($attributes['ville_arrivee_id'])) {
            $attributes['ville_arrivee_id'] = $this->villeFromLabel(
                is_string($arrivee) && $arrivee !== '' ? $arrivee : "arrivee{$counter}"
            )->id;
        }

        $lookup = [
            'ville_depart_id' => $attributes['ville_depart_id'],
            'ville_arrivee_id' => $attributes['ville_arrivee_id'],
        ];

        $destination = Destination::query()->firstOrCreate(
            $lookup,
            array_merge([
                'montant' => null,
                'commission_pourcentage' => null,
                'appliquer_configuration' => false,
                'actif' => true,
            ], $attributes),
        );

        if ($attributes !== []) {
            $destination->fill($attributes)->save();
        }

        if ($agence) {
            $destination->agences()->syncWithoutDetaching([$agence->id]);
        }

        return $destination->fresh(['villeDepart', 'villeArrivee']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createOffreForAgence(Agence $agence, array $attributes = []): Offre
    {
        $depart = $attributes['depart'] ?? $attributes['origine'] ?? null;
        $arrivee = $attributes['arrivee'] ?? $attributes['destination'] ?? null;
        unset($attributes['depart'], $attributes['arrivee'], $attributes['origine'], $attributes['destination']);

        if (! isset($attributes['destination_id'])) {
            $destinationAttrs = [];
            if (is_string($depart) && $depart !== '') {
                $destinationAttrs['depart'] = $depart;
            }
            if (is_string($arrivee) && $arrivee !== '') {
                $destinationAttrs['arrivee'] = $arrivee;
            }

            $destination = $this->createDestination($destinationAttrs, $agence);
            $attributes['destination_id'] = $destination->id;
        } else {
            $destination = Destination::query()->find($attributes['destination_id']);
            if ($destination) {
                $destination->agences()->syncWithoutDetaching([$agence->id]);
            }
        }

        return Offre::query()->create(array_merge([
            'agence_id' => $agence->id,
            'titre' => 'Offre test',
            'type' => 'particulier',
            'prix' => 2500,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'statut' => 'active',
        ], $attributes));
    }
}
