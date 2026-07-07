<?php

namespace App\Http\Resources\Api\Agence;

use App\Http\Resources\Api\PaiementResource;
use App\Models\Commande;
use App\Support\CommandeClientPresenter;
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
            'created_at' => $this->created_at?->toIso8601String(),
            'client' => CommandeClientPresenter::for($this->resource),
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
