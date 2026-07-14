<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgenceRequest;
use App\Models\Agence;
use App\Models\TypeAgence;
use App\Models\User;
use App\Services\AgenceMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AgenceController extends Controller
{
    public function __construct(
        private readonly AgenceMediaService $media,
    ) {}

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

    public function store(StoreAgenceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => $data['gerant_name'],
                'email' => $data['gerant_email'],
                'password' => $data['gerant_password'],
                'role' => 'agence',
            ]);

            $agence = Agence::create([
                'user_id' => $user->id,
                'type_agence_id' => $data['type_agence_id'] ?? null,
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'ville' => $data['ville'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'pays' => $data['pays'] ?? 'Gabon',
                'statut' => 'actif',
            ]);

            if ($request->hasFile('logo')) {
                /** @var UploadedFile $logo */
                $logo = $request->file('logo');
                $this->media->storeLogo($agence, $logo);
            }

            /** @var array<int, array{fichier: UploadedFile, type_document: string}> $documents */
            $documents = $data['documents'] ?? [];

            if ($documents !== []) {
                $this->media->storeDocuments($agence, $documents);
            }
        });

        return back()->with('success', "L'agence \"{$data['nom']}\" a été créée avec succès.");
    }

    public function show(Agence $agence): Response
    {
        $agence->load(['user:id,name,email', 'typeAgence:id,nom', 'logo', 'documents']);

        $finance = DB::table('vue_agences_soldes')
            ->where('agence_id', $agence->id)
            ->first();

        $stats = [
            'nb_offres' => $agence->offres()->count(),
            'nb_commandes' => $agence->commandes()->count(),
            'montant_paiements_valides' => (float) ($finance->montant_paiements_valides ?? 0),
            'montant_reversements' => (float) ($finance->montant_reversements ?? 0),
            'montant_solde' => (float) ($finance->montant_solde ?? 0),
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
