<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Client::withCount('commandes');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/clients/index', [
            'clients' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function show(Client $client): Response
    {
        $client->load(['user:id,name,email']);

        $stats = [
            'nb_commandes' => $client->commandes()->count(),
            'nb_reclamations' => $client->reclamations()->count(),
            'total_paiements' => (float) Paiement::join('commandes', 'paiements.commande_id', '=', 'commandes.id')
                ->where('commandes.client_id', $client->id)
                ->where('paiements.statut', 'validé')
                ->sum('paiements.montant'),
        ];

        $commandes = $client->commandes()
            ->with('agence:id,nom')
            ->latest()
            ->limit(10)
            ->get(['id', 'code', 'agence_id', 'quantite', 'montant_total', 'statut', 'created_at']);

        $reclamations = $client->reclamations()
            ->latest()
            ->limit(10)
            ->get(['id', 'objet', 'statut', 'created_at']);

        return Inertia::render('admin/clients/show', [
            'client' => $client,
            'stats' => $stats,
            'commandes' => $commandes,
            'reclamations' => $reclamations,
        ]);
    }
}
