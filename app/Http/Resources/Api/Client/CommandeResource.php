<?php

namespace App\Http\Resources\Api\Client;

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
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'created_at' => $this->created_at?->toIso8601String(),
            'agence' => $this->whenLoaded('agence', fn () => [
                'id' => $this->agence?->id,
                'nom' => $this->agence?->nom,
            ]),
            'offre' => $this->whenLoaded('offre', fn () => [
                'id' => $this->offre?->id,
                'titre' => $this->offre?->titre,
                'type' => $this->offre?->type,
            ]),
            'paiement' => PaiementResource::make($this->whenLoaded('paiement')),
            'colis' => ColisResource::collection($this->whenLoaded('colis')),
        ];
    }
}
