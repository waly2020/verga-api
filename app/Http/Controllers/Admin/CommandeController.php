<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommandeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Commande::with([
            'client:id,nom,prenom,email',
            'agence:id,nom',
        ]);

        if ($search = $request->get('search')) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/commandes/index', [
            'commandes' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function show(Commande $commande): Response
    {
        $commande->load([
            'client:id,nom,prenom,email,telephone',
            'agence:id,nom,email,ville',
            'offre:id,titre,type,prix,origine,destination,statut',
            'paiement',
            'commission',
            'colis:id,commande_id,reference,statut,created_at',
            'reclamations:id,commande_id,objet,statut,created_at',
        ]);

        return Inertia::render('admin/commandes/show', [
            'commande' => $commande,
        ]);
    }
}
