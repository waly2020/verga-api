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
            'offre:id,type_offre_id',
            'offre.typeOffre:id,unite,quantite_entier',
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
            'offre:id,titre,type,type_offre_id,prix,origine,destination,statut,capacite_totale,capacite_disponible',
            'offre.typeOffre:id,unite,unite_label,quantite_entier',
            'paiement:id,commande_id,code,quantite,reference,bamboo_reference,montant,montant_sous_total,montant_commission_client,methode,statut,created_at',
            'paiements' => fn ($q) => $q
                ->select('id', 'commande_id', 'code', 'quantite', 'reference', 'bamboo_reference', 'montant', 'montant_sous_total', 'montant_commission_client', 'methode', 'statut', 'created_at')
                ->latest(),
            'commission',
            'colis:id,commande_id,reference,description,statut,created_at',
            'reclamations:id,commande_id,objet,statut,created_at',
        ]);

        return Inertia::render('admin/commandes/show', [
            'commande' => $commande,
        ]);
    }
}
