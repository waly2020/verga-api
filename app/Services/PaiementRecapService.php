<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Paiement;
use App\Support\PaiementReturnUrl;
use App\Support\QuantiteFormatter;

class PaiementRecapService
{
    /**
     * @return array<string, mixed>
     */
    public function forPage(Paiement $paiement): array
    {
        $data = $this->build($paiement);

        $data['facture_url'] = route('paiement.facture', ['paiement' => $paiement->code]);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function forPdf(Paiement $paiement): array
    {
        return $this->build($paiement);
    }

    public function findByCode(string $code): Paiement
    {
        return Paiement::query()
            ->where('code', $code)
            ->with($this->relations())
            ->firstOrFail();
    }

    private function relations(): array
    {
        return [
            'commande.client:id,nom,prenom,email,telephone',
            'commande.agence:id,nom,email,telephone,ville',
            'commande.offre.typeOffre',
            'commande.colis:id,commande_id,reference,description,statut',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Paiement $paiement): array
    {
        $paiement->loadMissing($this->relations());

        $commande = $paiement->commande;
        $offre = $commande?->offre;
        $typeOffre = $offre?->typeOffre;
        $client = $commande?->client;

        $clientNom = $client
            ? trim("{$client->prenom} {$client->nom}")
            : trim("{$commande?->prenom} {$commande?->nom}");

        return [
            'paiement' => [
                'code' => $paiement->code,
                'statut' => $paiement->statut,
                'methode' => $paiement->methode,
                'reference' => $paiement->reference,
                'bamboo_reference' => $paiement->bamboo_reference,
                'bamboo_message' => $paiement->bamboo_message,
                'created_at' => $paiement->created_at?->toIso8601String(),
                'quantite' => (float) $paiement->quantite,
                'quantite_label' => QuantiteFormatter::format($paiement->quantite, $typeOffre),
                'montant_sous_total' => (float) $paiement->montant_sous_total,
                'montant_commission_client' => (float) $paiement->montant_commission_client,
                'montant' => (float) $paiement->montant,
            ],
            'commande' => $commande ? [
                'code' => $commande->code,
                'statut' => $commande->statut,
                'quantite' => (float) $commande->quantite,
                'quantite_label' => QuantiteFormatter::format($commande->quantite, $typeOffre),
                'quantite_payee' => (float) $commande->quantite_payee,
                'quantite_payee_label' => QuantiteFormatter::format($commande->quantite_payee, $typeOffre),
                'quantite_restante' => $commande->quantiteRestante(),
                'quantite_restante_label' => QuantiteFormatter::format($commande->quantiteRestante(), $typeOffre),
                'montant_sous_total' => (float) $commande->montant_sous_total,
                'montant_commission_client' => (float) $commande->montant_commission_client,
                'montant_total' => (float) $commande->montant_total,
            ] : null,
            'client' => [
                'nom' => $clientNom ?: null,
                'email' => $client?->email,
                'telephone' => $client?->telephone ?? $commande?->telephone,
            ],
            'agence' => $commande?->agence ? [
                'nom' => $commande->agence->nom,
                'email' => $commande->agence->email,
                'telephone' => $commande->agence->telephone,
                'ville' => $commande->agence->ville,
            ] : null,
            'offre' => $offre ? [
                'titre' => $offre->titre,
                'origine' => $offre->origine,
                'destination' => $offre->destination,
                'prix' => (float) $offre->prix,
                'type_offre' => $typeOffre ? [
                    'nom' => $typeOffre->nom,
                    'unite_label' => $typeOffre->unite_label,
                ] : null,
            ] : null,
            'colis' => $commande?->colis
                ->map(fn ($colis) => [
                    'reference' => $colis->reference,
                    'description' => $colis->description,
                    'statut' => $colis->statut,
                ])
                ->values()
                ->all(),
            'retour_url' => PaiementReturnUrl::for($paiement),
        ];
    }
}
