<?php

namespace App\Http\Resources\Api\Client;

use App\Http\Resources\Api\PaiementResource;
use App\Models\Commande;
use App\Support\QuantiteFormatter;
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
        $typeOffre = $this->offre?->typeOffre;

        return [
            'id' => $this->id,
            'code' => $this->code,
            ...QuantiteFormatter::withLabels([
                'quantite' => $this->quantite,
                'quantite_payee' => $this->quantite_payee,
                'quantite_restante' => $this->quantiteRestante(),
            ], $typeOffre),
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
            'offre' => OffreResource::make($this->whenLoaded('offre')),
            'paiement' => $this->whenLoaded('paiement', function () {
                $this->paiement->setRelation('commande', $this->resource);

                return PaiementResource::make($this->paiement);
            }),
            'colis' => $this->whenLoaded('colis', fn () => $this->colis->map(
                fn ($colis) => ColisResource::make($colis)->withQuantiteTypeOffre($typeOffre),
            )),
        ];
    }
}
