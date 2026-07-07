<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\Commission;
use App\Models\Paiement;
use App\Models\TypeAgence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AgenceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Agence::withCount('offres');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/agences/index', [
            'agences' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
            'types_agences' => TypeAgence::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'gerant_name' => ['required', 'string', 'max:255'],
            'gerant_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'gerant_password' => ['required', 'confirmed', Password::min(8)],
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:agences,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'type_agence_id' => ['nullable', 'uuid', 'exists:type_agences,id'],
            'ville' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'pays' => ['nullable', 'string', 'max:100'],
        ], [
            'gerant_name.required' => 'Le nom du gérant est obligatoire.',
            'gerant_email.required' => "L'email du gérant est obligatoire.",
            'gerant_email.unique' => 'Cet email est déjà utilisé.',
            'gerant_password.required' => 'Le mot de passe est obligatoire.',
            'gerant_password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'nom.required' => "Le nom de l'agence est obligatoire.",
            'email.required' => "L'email de l'agence est obligatoire.",
            'email.unique' => 'Cet email est déjà utilisé par une autre agence.',
            'telephone.required' => 'Le téléphone est obligatoire.',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->gerant_name,
                'email' => $request->gerant_email,
                'password' => $request->gerant_password,
                'role' => 'agence',
            ]);

            Agence::create([
                'user_id' => $user->id,
                'type_agence_id' => $request->type_agence_id,
                'nom' => $request->nom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'ville' => $request->ville,
                'adresse' => $request->adresse,
                'pays' => $request->pays ?? 'Gabon',
                'statut' => 'actif',
            ]);
        });

        return back()->with('success', "L'agence \"{$request->nom}\" a été créée avec succès.");
    }

    public function show(Agence $agence): Response
    {
        $agence->load(['user:id,name,email', 'typeAgence:id,nom']);

        $stats = [
            'nb_offres' => $agence->offres()->count(),
            'nb_commandes' => $agence->commandes()->count(),
            'total_paiements' => (float) Paiement::join('commandes', 'paiements.commande_id', '=', 'commandes.id')
                ->where('commandes.agence_id', $agence->id)
                ->where('paiements.statut', 'validé')
                ->sum('paiements.montant'),
            'total_commissions' => (float) Commission::join('commandes', 'commissions.commande_id', '=', 'commandes.id')
                ->where('commandes.agence_id', $agence->id)
                ->sum('commissions.montant'),
        ];

        $offres = $agence->offres()
            ->latest()
            ->get(['id', 'titre', 'type', 'prix', 'statut', 'origine', 'destination']);

        $commandes = $agence->commandes()
            ->with([
                'client:id,nom,prenom',
                'offre:id,type_offre_id',
                'offre.typeOffre:id,unite,quantite_entier',
            ])
            ->latest()
            ->limit(10)
            ->get(['id', 'code', 'client_id', 'offre_id', 'quantite', 'montant_total', 'statut', 'created_at']);

        return Inertia::render('admin/agences/show', [
            'agence' => $agence,
            'stats' => $stats,
            'offres' => $offres,
            'commandes' => $commandes,
        ]);
    }

    public function updateStatut(Agence $agence): RedirectResponse
    {
        $nouveau = $agence->statut === 'bloqué' ? 'actif' : 'bloqué';
        $agence->update(['statut' => $nouveau]);

        $msg = $nouveau === 'bloqué'
            ? "L'agence \"{$agence->nom}\" a été bloquée."
            : "L'agence \"{$agence->nom}\" a été débloquée.";

        return back()->with('success', $msg);
    }

    public function destroy(Agence $agence): RedirectResponse
    {
        $nom = $agence->nom;
        $agence->delete();

        return redirect()
            ->route('admin.agences.index')
            ->with('success', "L'agence \"{$nom}\" a été supprimée.");
    }
}
