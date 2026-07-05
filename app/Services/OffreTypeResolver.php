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
    public function resolveForCreate(array $data): array
    {
        if (! empty($data['type_offre_id'])) {
            $typeOffre = TypeOffre::query()
                ->actif()
                ->find($data['type_offre_id']);

            if (! $typeOffre) {
                throw ValidationException::withMessages([
                    'type_offre_id' => ['Ce type d\'offre est invalide ou inactif.'],
                ]);
            }

            $data['type'] = $typeOffre->slug;

            return $data;
        }

        if (! empty($data['type'])) {
            $typeOffre = TypeOffre::query()
                ->actif()
                ->where('slug', $data['type'])
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
}
