<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDestinationRequest;
use App\Http\Requests\Admin\UpdateDestinationRequest;
use App\Models\Destination;
use App\Services\DestinationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    public function __construct(
        private DestinationResolver $destinationResolver,
    ) {}

    public function index(Request $request): Response
    {
        $query = Destination::query()->withCount('offres');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('depart', 'like', "%{$search}%")
                    ->orWhere('arrivee', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        return Inertia::render('admin/destinations/index', [
            'destinations' => $query->orderBy('depart')->orderBy('arrivee')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'actif']),
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
            "Destination « {$destination->depart} → {$destination->arrivee} » créée avec succès."
        );
    }

    public function update(UpdateDestinationRequest $request, Destination $destination): RedirectResponse
    {
        $validated = $request->validated();

        $depart = $this->destinationResolver->normalizeLabel($validated['depart']);
        $arrivee = $this->destinationResolver->normalizeLabel($validated['arrivee']);

        if ($depart === '' || $arrivee === '') {
            throw ValidationException::withMessages([
                'depart' => ['Le départ et l\'arrivée sont obligatoires.'],
            ]);
        }

        $duplicate = Destination::query()
            ->where('depart', $depart)
            ->where('arrivee', $arrivee)
            ->whereKeyNot($destination->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'depart' => ['Cette destination existe déjà.'],
            ]);
        }

        $appliquer = $request->boolean('appliquer_configuration');

        $destination->update([
            'depart' => $depart,
            'arrivee' => $arrivee,
            'appliquer_configuration' => $appliquer,
            'montant' => $appliquer ? $validated['montant'] : null,
            'commission_pourcentage' => $appliquer ? $validated['commission_pourcentage'] : null,
            'actif' => $request->boolean('actif'),
        ]);

        return back()->with(
            'success',
            "Destination « {$depart} → {$arrivee} » mise à jour."
        );
    }

    public function destroy(Destination $destination): RedirectResponse
    {
        if ($destination->offres()->exists()) {
            return back()->with('error', 'Impossible de supprimer une destination utilisée par des offres existantes.');
        }

        $label = "{$destination->depart} → {$destination->arrivee}";
        $destination->delete();

        return back()->with('success', "Destination « {$label} » supprimée.");
    }
}
