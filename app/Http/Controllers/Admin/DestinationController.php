<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDestinationRequest;
use App\Http\Requests\Admin\UpdateDestinationRequest;
use App\Models\Destination;
use App\Models\Ville;
use App\Services\DestinationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    public function __construct(
        private DestinationResolver $destinationResolver,
    ) {}

    public function index(Request $request): Response
    {
        $query = Destination::query()
            ->with(['villeDepart', 'villeArrivee'])
            ->withCount('offres');

        if ($search = $request->get('search')) {
            $query->matchingLocalite($search);
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        return Inertia::render('admin/destinations/index', [
            'destinations' => $query->orderByTrajet()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'actif']),
            'villes' => Ville::query()->orderBy('pays')->orderBy('ville')->get(['id', 'pays', 'ville', 'code', 'actif']),
        ]);
    }

    public function store(StoreDestinationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['appliquer_configuration'] = $request->boolean('appliquer_configuration');
        $validated['actif'] = $request->boolean('actif', true);

        if (! $validated['appliquer_configuration']) {
            $validated['montant'] = null;
            $validated['commission_pourcentage'] = null;
        }

        $destination = $this->destinationResolver->createForAdmin($validated);

        return back()->with(
            'success',
            "Destination « {$destination->trajetLabel()} » créée avec succès."
        );
    }

    public function update(UpdateDestinationRequest $request, Destination $destination): RedirectResponse
    {
        $validated = $request->validated();
        $trajet = $this->destinationResolver->resolveVillePair(
            $validated['ville_depart_id'],
            $validated['ville_arrivee_id'],
        );
        $this->destinationResolver->assertUniqueTrajet(
            $trajet['ville_depart_id'],
            $trajet['ville_arrivee_id'],
            $destination->id,
        );

        $appliquer = $request->boolean('appliquer_configuration');

        $destination->update([
            'ville_depart_id' => $trajet['ville_depart_id'],
            'ville_arrivee_id' => $trajet['ville_arrivee_id'],
            'appliquer_configuration' => $appliquer,
            'montant' => $appliquer ? $validated['montant'] : null,
            'commission_pourcentage' => $appliquer ? $validated['commission_pourcentage'] : null,
            'actif' => $request->boolean('actif'),
        ]);

        return back()->with(
            'success',
            "Destination « {$destination->fresh()->trajetLabel()} » mise à jour."
        );
    }

    public function destroy(Destination $destination): RedirectResponse
    {
        if ($destination->offres()->exists()) {
            return back()->with('error', 'Impossible de supprimer une destination utilisée par des offres existantes.');
        }

        $label = $destination->trajetLabel();
        $destination->delete();

        return back()->with('success', "Destination « {$label} » supprimée.");
    }
}
