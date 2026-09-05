<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVilleRequest;
use App\Http\Requests\Admin\UpdateVilleRequest;
use App\Models\Ville;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VilleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Ville::query()
            ->withCount(['destinationsDepart', 'destinationsArrivee']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('pays', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        return Inertia::render('admin/villes/index', [
            'villes' => $query->orderBy('pays')->orderBy('ville')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'actif']),
            'pays_existants' => Ville::nomsPays(false)->values(),
        ]);
    }

    public function store(StoreVilleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['actif'] = $request->boolean('actif', true);

        Ville::create($validated);

        return back()->with(
            'success',
            "Ville « {$validated['ville']} ({$validated['pays']}) » créée avec succès."
        );
    }

    public function update(UpdateVilleRequest $request, Ville $ville): RedirectResponse
    {
        $validated = $request->validated();
        $validated['actif'] = $request->boolean('actif');

        $ville->update($validated);

        return back()->with(
            'success',
            "Ville « {$validated['ville']} ({$validated['pays']}) » mise à jour."
        );
    }

    public function destroy(Ville $ville): RedirectResponse
    {
        if ($ville->isUsedByDestinations()) {
            return back()->with('error', 'Impossible de supprimer une ville utilisée par des destinations.');
        }

        $label = $ville->label();
        $ville->delete();

        return back()->with('success', "Ville « {$label} » supprimée.");
    }
}
