<?php

namespace App\Services;

use App\Models\TypeOffre;
use Illuminate\Validation\ValidationException;

class OffreTypeResolver
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolveForCreate(array $data, ?string $agenceId = null): array
    {
        if (! empty($data['type_offre_id'])) {
            $typeOffre = $this->findAvailableType($data['type_offre_id'], $agenceId);

            if (! $typeOffre) {
                throw ValidationException::withMessages([
                    'type_offre_id' => ['Ce type d\'offre est invalide ou inactif.'],
                ]);
            }

            $data['type'] = $typeOffre->slug;

            return $data;
        }

        if (! empty($data['type'])) {
            $typeOffre = $this->queryAvailableTypes($agenceId)
                ->where('slug', $data['type'])
                ->orderByRaw('agence_id is null desc')
                ->first();

            if ($typeOffre) {
                $data['type_offre_id'] = $typeOffre->id;
            }

            return $data;
        }

        throw ValidationException::withMessages([
            'type_offre_id' => ['Le type d\'offre est obligatoire.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolveForUpdate(array $data, ?string $agenceId = null): array
    {
        if (! empty($data['type_offre_id']) || ! empty($data['type'])) {
            return $this->resolveForCreate($data, $agenceId);
        }

        return $data;
    }

    private function findAvailableType(string $typeOffreId, ?string $agenceId): ?TypeOffre
    {
        return $this->queryAvailableTypes($agenceId)
            ->where('id', $typeOffreId)
            ->first();
    }

    private function queryAvailableTypes(?string $agenceId)
    {
        $query = TypeOffre::query()->actif();

        if ($agenceId) {
            return $query->availableForAgence($agenceId);
        }

        return $query->platform();
    }
}
