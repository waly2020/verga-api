<?php

namespace App\Services;

use App\Models\Offre;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientOffreCatalogService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Offre::query()
            ->with([
                'agence:id,nom,ville',
                'agence.logo',
                'typeOffre:id,slug,nom,unite,unite_label,quantite_entier,quantite_min',
                'destination:id,depart,arrivee,montant,commission_pourcentage,appliquer_configuration,actif',
            ])
            ->active()
            ->whereHas('destination', fn ($q) => $q->actif())
            ->where(function ($q) {
                $q->where('capacite_illimitee', true)
                    ->orWhere('capacite_disponible', '>', 0);
            });

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('destination', function ($dq) use ($search) {
                        $dq->where('depart', 'like', "%{$search}%")
                            ->orWhere('arrivee', 'like', "%{$search}%");
                    });
            });
        }

        if ($destination = $filters['destination'] ?? null) {
            $query->whereHas('destination', function ($dq) use ($destination) {
                $dq->where('arrivee', 'like', "%{$destination}%")
                    ->orWhere('depart', 'like', "%{$destination}%");
            });
        }

        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }

        if ($typeOffreId = $filters['type_offre_id'] ?? null) {
            $query->where('type_offre_id', $typeOffreId);
        }

        if ($dateDebut = $filters['date_debut'] ?? null) {
            $query->whereDate('created_at', '>=', $dateDebut);
        }

        if ($dateFin = $filters['date_fin'] ?? null) {
            $query->whereDate('created_at', '<=', $dateFin);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
