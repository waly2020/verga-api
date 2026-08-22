<?php

namespace App\Http\Resources\Api;

use App\Models\Publicite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Publicite */
class PubliciteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'lien' => $this->lien,
            'image' => $this->image_chemin ? [
                'chemin' => $this->image_chemin,
                'url' => $this->image_url,
                'nom_original' => $this->image_nom_original,
            ] : null,
            'date_debut' => $this->date_debut?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'nombre_jours' => $this->nombre_jours,
            'statut' => $this->statut,
            'statut_paiement' => $this->statut_paiement,
            'motif_refus' => $this->motif_refus,
            'offre_id' => $this->offre_id,
            'offre' => $this->whenLoaded('offre', fn () => $this->offre ? [
                'id' => $this->offre->id,
                'titre' => $this->offre->titre,
            ] : null),
            'agence' => $this->whenLoaded('agence', fn () => $this->agence ? [
                'id' => $this->agence->id,
                'nom' => $this->agence->nom,
            ] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'nom' => $this->client->nom,
                'prenom' => $this->client->prenom,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
