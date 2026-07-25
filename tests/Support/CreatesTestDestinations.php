<?php

namespace Tests\Support;

use App\Models\Agence;
use App\Models\Destination;
use App\Models\Offre;
use App\Services\DestinationResolver;

trait CreatesTestDestinations
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createDestination(array $attributes = [], ?Agence $agence = null): Destination
    {
        static $counter = 0;
        $counter++;

        $depart = $attributes['depart'] ?? "depart{$counter}";
        $arrivee = $attributes['arrivee'] ?? "arrivee{$counter}";
        unset($attributes['depart'], $attributes['arrivee']);

        $resolver = app(DestinationResolver::class);
        $depart = $resolver->normalizeLabel((string) $depart);
        $arrivee = $resolver->normalizeLabel((string) $arrivee);

        $destination = Destination::query()->firstOrCreate(
            [
                'depart' => $depart,
                'arrivee' => $arrivee,
            ],
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

        return $destination->fresh();
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
