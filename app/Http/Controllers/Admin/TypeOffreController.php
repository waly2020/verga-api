<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTypeOffreRequest;
use App\Http\Requests\Admin\UpdateTypeOffreRequest;
use App\Models\TypeOffre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TypeOffreController extends Controller
{
    public function index(Request $request): Response
    {
        $query = TypeOffre::query()->withCount('offres');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('unite', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        return Inertia::render('admin/types-offres/index', [
            'types_offres' => $query->orderBy('nom')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'actif']),
        ]);
    }

    public function store(StoreTypeOffreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['quantite_entier'] = $request->boolean('quantite_entier');
        $validated['actif'] = $request->boolean('actif', true);

        TypeOffre::create($validated);

        return back()->with('success', "Type d'offre « {$validated['nom']} » créé avec succès.");
    }

    public function update(UpdateTypeOffreRequest $request, TypeOffre $typeOffre): RedirectResponse
    {
        $validated = $request->validated();
        $validated['quantite_entier'] = $request->boolean('quantite_entier');
        $validated['actif'] = $request->boolean('actif');

        $typeOffre->update($validated);

        return back()->with('success', "Type d'offre « {$validated['nom']} » mis à jour.");
    }

    public function destroy(TypeOffre $typeOffre): RedirectResponse
    {
        if ($typeOffre->offres()->exists()) {
            return back()->with('error', 'Impossible de supprimer un type utilisé par des offres existantes.');
        }

        $nom = $typeOffre->nom;
        $typeOffre->delete();

        return back()->with('success', "Type d'offre « {$nom} » supprimé.");
    }
}
