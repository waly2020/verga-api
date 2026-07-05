<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOffreRequest;
use App\Http\Requests\Admin\UpdateOffreRequest;
use App\Models\Agence;
use App\Models\Offre;
use App\Models\TypeOffre;
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
    ) {}

    public function index(Request $request): Response
    {
        $query = Offre::with(['agence:id,nom', 'typeOffre:id,slug,nom,unite_label']);

        if ($search = $request->get('search')) {
            $query->where('titre', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/offres/index', [
            'offres' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
            'agences' => Agence::where('statut', 'actif')->orderBy('nom')->get(['id', 'nom']),
            'types_offres' => TypeOffre::query()->actif()->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreOffreRequest $request): RedirectResponse
    {
        $data = $this->typeResolver->resolveForCreate($request->validated());
        $data['capacite_disponible'] = $data['capacite_totale'];

        Offre::create($data);

        return back()->with('success', "L'offre \"{$data['titre']}\" a été créée avec succès.");
    }

    public function update(UpdateOffreRequest $request, Offre $offre): RedirectResponse
    {
        $data = $this->typeResolver->resolveForUpdate($request->validated());
        $data = $this->capacite->applyTotaleUpdate($offre, $data);

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
