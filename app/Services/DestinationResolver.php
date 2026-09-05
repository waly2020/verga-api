<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Ville;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DestinationResolver
{
    /**
     * @param  array{
     *     ville_depart_id: string,
     *     ville_arrivee_id: string,
     *     montant?: float|string|null,
     *     commission_pourcentage?: float|string|null,
     *     appliquer_configuration?: bool,
     *     actif?: bool
     * }  $attributes
     */
    public function createForAdmin(array $attributes): Destination
    {
        $trajet = $this->resolveVillePair(
            (string) $attributes['ville_depart_id'],
            (string) $attributes['ville_arrivee_id'],
        );
        $this->assertUniqueTrajet($trajet['ville_depart_id'], $trajet['ville_arrivee_id']);

        $appliquer = (bool) ($attributes['appliquer_configuration'] ?? false);

        return Destination::create([
            'ville_depart_id' => $trajet['ville_depart_id'],
            'ville_arrivee_id' => $trajet['ville_arrivee_id'],
            'appliquer_configuration' => $appliquer,
            'montant' => $appliquer ? $attributes['montant'] : null,
            'commission_pourcentage' => $appliquer ? $attributes['commission_pourcentage'] : null,
            'actif' => (bool) ($attributes['actif'] ?? true),
        ]);
    }

    public function findOrCreateAndAttach(
        string $agenceId,
        string $villeDepartId,
        string $villeArriveeId,
    ): Destination {
        $trajet = $this->resolveVillePair($villeDepartId, $villeArriveeId);

        return $this->findOrCreateFromTrajet($agenceId, $trajet);
    }

    /**
     * @return array{ville_depart_id: string, ville_arrivee_id: string}
     */
    public function resolveVillePair(string $villeDepartId, string $villeArriveeId): array
    {
        $villeDepart = $this->findActiveVille($villeDepartId, 'ville_depart_id');
        $villeArrivee = $this->findActiveVille($villeArriveeId, 'ville_arrivee_id');

        if ($villeDepart->id === $villeArrivee->id) {
            throw ValidationException::withMessages([
                'ville_arrivee_id' => ['Le départ et l\'arrivée doivent être différents.'],
            ]);
        }

        return [
            'ville_depart_id' => $villeDepart->id,
            'ville_arrivee_id' => $villeArrivee->id,
        ];
    }

    public function attachToAgence(Destination $destination, string $agenceId): Destination
    {
        $destination->agences()->syncWithoutDetaching([$agenceId]);

        return $destination;
    }

    public function ensureAvailableForAgence(Destination $destination, string $agenceId): void
    {
        if (! $destination->actif) {
            throw ValidationException::withMessages([
                'destination_id' => ['Cette destination est inactive.'],
            ]);
        }

        $this->attachToAgence($destination, $agenceId);
    }

    public function assertUniqueTrajet(
        string $villeDepartId,
        string $villeArriveeId,
        ?string $exceptDestinationId = null,
    ): void {
        $duplicate = Destination::query()
            ->where('ville_depart_id', $villeDepartId)
            ->where('ville_arrivee_id', $villeArriveeId)
            ->when($exceptDestinationId, fn ($query) => $query->whereKeyNot($exceptDestinationId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'ville_depart_id' => ['Cette destination existe déjà.'],
            ]);
        }
    }

    /**
     * @param  array{ville_depart_id: string, ville_arrivee_id: string}  $trajet
     */
    private function findOrCreateFromTrajet(string $agenceId, array $trajet): Destination
    {
        return DB::transaction(function () use ($agenceId, $trajet) {
            $destination = Destination::query()
                ->lockForUpdate()
                ->where('ville_depart_id', $trajet['ville_depart_id'])
                ->where('ville_arrivee_id', $trajet['ville_arrivee_id'])
                ->first();

            if (! $destination) {
                $destination = Destination::create([
                    'ville_depart_id' => $trajet['ville_depart_id'],
                    'ville_arrivee_id' => $trajet['ville_arrivee_id'],
                    'appliquer_configuration' => false,
                    'montant' => null,
                    'commission_pourcentage' => null,
                    'actif' => true,
                ]);
            } elseif (! $destination->actif) {
                throw ValidationException::withMessages([
                    'ville_depart_id' => ['Cette destination existe mais est inactive. Demandez à un administrateur de la réactiver.'],
                ]);
            }

            $destination->agences()->syncWithoutDetaching([$agenceId]);

            return $destination->fresh(['villeDepart', 'villeArrivee']);
        });
    }

    private function findActiveVille(string $villeId, string $errorKey): Ville
    {
        $ville = Ville::query()->find($villeId);

        if (! $ville) {
            throw ValidationException::withMessages([
                $errorKey => ['Cette ville est introuvable.'],
            ]);
        }

        if (! $ville->actif) {
            throw ValidationException::withMessages([
                $errorKey => ['Les villes sélectionnées doivent être actives.'],
            ]);
        }

        return $ville;
    }
}
