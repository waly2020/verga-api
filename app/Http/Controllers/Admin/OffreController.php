<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOffreRequest;
use App\Http\Requests\Admin\UpdateOffreRequest;
use App\Models\Agence;
use App\Models\Destination;
use App\Models\Offre;
use App\Models\TypeOffre;
use App\Services\DestinationResolver;
use App\Services\OffreCapaciteService;
use App\Services\OffreTypeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OffreController extends Controller
{
    public function __construct(
        private readonly OffreTypeResolver $typeResolver,
        private readonly OffreCapaciteService $capacite,
        private readonly DestinationResolver $destinationResolver,
    ) {}

    public function index(Request $request): Response
    {
        $query = Offre::with([
            'agence:id,nom',
            'agence.logo',
            'typeOffre:id,slug,nom,unite_label',
            'destination:id,ville_depart_id,ville_arrivee_id,montant,commission_pourcentage,appliquer_configuration,actif',
            'destination.villeDepart:id,pays,ville,code',
            'destination.villeArrivee:id,pays,ville,code',
        ]);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhereHas('destination', fn ($dq) => $dq->matchingLocalite($search));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        $offres = $query->latest()->paginate(15)->withQueryString();

        $destinationIdsOnPage = $offres->getCollection()
            ->pluck('destination_id')
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('admin/offres/index', [
            'offres' => $offres,
            'filters' => $request->only(['search', 'statut']),
            'agences' => Agence::where('statut', 'actif')->orderBy('nom')->get(['id', 'nom']),
            'types_offres' => TypeOffre::query()->actif()->orderBy('nom')->get(),
            'destinations' => Destination::query()
                ->with(['villeDepart:id,pays,ville,code', 'villeArrivee:id,pays,ville,code'])
                ->where(function ($q) use ($destinationIdsOnPage) {
                    $q->where('actif', true);

                    if ($destinationIdsOnPage->isNotEmpty()) {
                        $q->orWhereIn('id', $destinationIdsOnPage);
                    }
                })
                ->orderByTrajet()
                ->get([
                    'id',
                    'ville_depart_id',
                    'ville_arrivee_id',
                    'montant',
                    'commission_pourcentage',
                    'appliquer_configuration',
                    'actif',
                ]),
        ]);
    }

    public function store(StoreOffreRequest $request): RedirectResponse
    {
        $data = $this->typeResolver->resolveForCreate($request->validated());
        $data = $this->capacite->normalizeForCreate($data);

        $destination = Destination::query()->findOrFail($data['destination_id']);
        $this->destinationResolver->attachToAgence($destination, $data['agence_id']);

        Offre::create($data);

        return back()->with('success', "L'offre \"{$data['titre']}\" a été créée avec succès.");
    }

    public function update(UpdateOffreRequest $request, Offre $offre): RedirectResponse
    {
        $data = $this->typeResolver->resolveForUpdate($request->validated());
        $data = $this->capacite->applyTotaleUpdate($offre, $data);

        $destination = Destination::query()->findOrFail($data['destination_id']);
        $this->destinationResolver->attachToAgence($destination, $data['agence_id']);

        $offre->update($data);

        return back()->with('success', "L'offre \"{$data['titre']}\" a été mise à jour.");
    }

    public function destroy(Offre $offre): RedirectResponse
    {
        if ($offre->commandes()->exists()) {
            return back()->with('error', 'Impossible de supprimer une offre liée à des commandes existantes.');
        }

        $titre = $offre->titre;
        $offre->delete();

        return back()->with('success', "L'offre \"{$titre}\" a été supprimée.");
    }
}
