<?php

namespace App\Http\Resources\Api\Agence;

use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Commande */
class CommandeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'quantite' => $this->quantite,
            'quantite_payee' => $this->quantite_payee,
            'quantite_restante' => $this->quantiteRestante(),
            'montant_sous_total' => $this->montant_sous_total,
            'montant_commission_client' => $this->montant_commission_client,
            'montant_total' => $this->montant_total,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client?->id,
                'nom' => $this->client?->nom,
                'prenom' => $this->client?->prenom,
                'email' => $this->client?->email,
            ]),
            'offre' => OffreResource::make($this->whenLoaded('offre')),
            'paiement' => PaiementResource::make($this->whenLoaded('paiement')),
            'colis' => ColisResource::collection($this->whenLoaded('colis')),
        ];
    }
}
