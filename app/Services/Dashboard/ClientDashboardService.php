<?php

namespace App\Services\Dashboard;

use App\Models\Client;
use App\Models\Colis;
use App\Support\PeriodeFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ClientDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Client $client, string $periode = 'mois'): array
    {
        [$debut, $fin] = PeriodeFilter::range($periode);

        $commandesQuery = $client->commandes()->whereBetween('created_at', [$debut, $fin]);

        $paiements = ValidatedPaiementStats::aggregate(
            $debut,
            $fin,
            fn (Builder $query) => $query
                ->join('commandes', 'paiements.commande_id', '=', 'commandes.id')
                ->where('commandes.client_id', $client->id),
        );

        $colisQuery = Colis::query()
            ->whereHas('commande', fn (Builder $query) => $query->where('client_id', $client->id))
            ->whereBetween('created_at', [$debut, $fin]);

        $reclamationsQuery = $client->reclamations()->whereBetween('created_at', [$debut, $fin]);

        return [
            'periode' => $periode,
            'debut' => $debut->toIso8601String(),
            'fin' => $fin->toIso8601String(),
            'profil' => [
                'type' => $client->type,
                'nom' => $client->nom,
                'prenom' => $client->prenom,
            ],
            'stats' => [
                'nb_commandes' => (clone $commandesQuery)->count(),
                'nb_commandes_en_attente' => (clone $commandesQuery)->where('statut', 'en_attente')->count(),
                'nb_commandes_confirmees' => (clone $commandesQuery)->where('statut', 'confirmée')->count(),
                'nb_colis' => (clone $colisQuery)->count(),
                'nb_colis_en_transit' => (clone $colisQuery)->where('statut', 'en_transit')->count(),
                'nb_colis_arrives' => (clone $colisQuery)->whereIn('statut', ['arrivé', 'récupéré'])->count(),
                'total_depense' => $paiements['total'],
                'total_sous_total' => $paiements['sous_total'],
                'total_commissions' => $paiements['commissions_client'],
                'nb_reclamations' => (clone $reclamationsQuery)->count(),
                'nb_reclamations_ouvertes' => (clone $reclamationsQuery)->whereIn('statut', ['ouverte', 'en_cours'])->count(),
            ],
            'commandes_par_statut' => $this->groupByStatut(clone $commandesQuery, 'statut'),
            'colis_par_statut' => $this->groupColisByStatut($client, $debut, $fin),
            'dernieres_commandes' => $this->recentCommandes($client),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function groupByStatut(Builder|Relation $query, string $column): array
    {
        return $query
            ->selectRaw("{$column}, COUNT(*) as total")
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function groupColisByStatut(Client $client, Carbon $debut, Carbon $fin): array
    {
        return Colis::query()
            ->whereHas('commande', fn (Builder $query) => $query->where('client_id', $client->id))
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentCommandes(Client $client): array
    {
        return $client->commandes()
            ->with(['agence:id,nom'])
            ->latest()
            ->limit(5)
            ->get([
                'id',
                'code',
                'agence_id',
                'montant_sous_total',
                'montant_commission_client',
                'montant_total',
                'statut',
                'created_at',
            ])
            ->map(fn ($commande) => [
                'id' => $commande->id,
                'code' => $commande->code,
                'montant_sous_total' => $commande->montant_sous_total,
                'montant_commission_client' => $commande->montant_commission_client,
                'montant_total' => $commande->montant_total,
                'statut' => $commande->statut,
                'created_at' => $commande->created_at?->toIso8601String(),
                'agence' => $commande->agence ? [
                    'id' => $commande->agence->id,
                    'nom' => $commande->agence->nom,
                ] : null,
            ])
            ->values()
            ->all();
    }
}
