<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colis;
use App\Services\ColisStatutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ColisController extends Controller
{
    public function __construct(
        private readonly ColisStatutService $statutService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Colis::with([
            'commande:id,code,quantite,offre_id',
            'commande.offre:id,type_offre_id',
            'commande.offre.typeOffre:id,unite,quantite_entier',
            'agence:id,nom',
        ]);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('commande', fn ($q) => $q->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('agence', fn ($q) => $q->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/colis/index', [
            'colis' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function show(Colis $colis): Response
    {
        $colis->load([
            'agence:id,nom,email,ville',
            'commande:id,code,client_id,offre_id,quantite,nom,prenom,telephone,montant_total,statut',
            'commande.offre:id,type_offre_id',
            'commande.offre.typeOffre:id,unite,quantite_entier',
            'commande.client:id,nom,prenom,email',
            'photos:id,colis_id,chemin,ordre',
            'historique' => fn ($q) => $q->with('actor')->latest(),
        ]);

        return Inertia::render('admin/colis/show', [
            'colis' => $colis,
            'next_statut' => ColisStatutService::FLUX[$colis->statut] ?? null,
        ]);
    }

    public function updateStatut(Request $request, Colis $colis): RedirectResponse
    {
        $validated = $request->validate([
            'date_statut' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $updated = $this->statutService->advance(
                $colis,
                $request->user(),
                commentaire: $validated['commentaire'] ?? null,
                dateStatut: $validated['date_statut'] ?? null,
            );
        } catch (ValidationException) {
            return back()->with('error', 'Ce colis est dans son statut final.');
        }

        $labels = [
            'déposé' => 'déposé à l\'agence',
            'en_transit' => 'en transit',
            'arrivé' => 'arrivé à destination',
            'récupéré' => 'récupéré par le client',
        ];

        $label = $labels[$updated->statut] ?? $updated->statut;

        return back()->with('success', "Colis {$colis->reference} marqué comme {$label}.");
    }
}
