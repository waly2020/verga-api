<?php

namespace App\Http\Resources\Api;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/** @mixin Destination */
#[OA\Schema(
    schema: 'DestinationResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'depart', type: 'string', example: 'chine'),
        new OA\Property(property: 'arrivee', type: 'string', example: 'libreville'),
        new OA\Property(property: 'montant', type: 'number', format: 'float', nullable: true, example: 8750),
        new OA\Property(property: 'commission_pourcentage', type: 'number', format: 'float', nullable: true, example: 10),
        new OA\Property(property: 'appliquer_configuration', type: 'boolean', example: false),
        new OA\Property(property: 'actif', type: 'boolean', example: true),
        new OA\Property(
            property: 'rattachee',
            type: 'boolean',
            description: 'Présent sur les endpoints agence : true si la destination est déjà liée à l\'agence authentifiée',
            example: true
        ),
    ]
)]
class DestinationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'depart' => $this->depart,
            'arrivee' => $this->arrivee,
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'commission_pourcentage' => $this->commission_pourcentage !== null
                ? (float) $this->commission_pourcentage
                : null,
            'appliquer_configuration' => (bool) $this->appliquer_configuration,
            'actif' => (bool) $this->actif,
            'rattachee' => $this->when(
                array_key_exists('rattachee', $this->resource->getAttributes()),
                (bool) $this->rattachee,
            ),
        ];
    }
}
