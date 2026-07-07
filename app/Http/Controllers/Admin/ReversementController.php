<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReversementRequest;
use App\Models\Agence;
use App\Models\Reversement;
use App\Services\Finance\AgenceSoldeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReversementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Reversement::with('agence:id,nom');

        if ($search = $request->get('search')) {
            $query->whereHas('agence', fn ($q) => $q->where('nom', 'like', "%{$search}%"));
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        $agences = Agence::query()
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Agence $agence) => [
                'id' => $agence->id,
                'nom' => $agence->nom,
                'montant_solde' => AgenceSoldeService::soldeCourant($agence->id),
                'montant_en_attente' => AgenceSoldeService::montantReversementsEnAttente($agence->id),
                'montant_disponible' => AgenceSoldeService::soldeDisponible($agence->id),
            ])
            ->values();

        return Inertia::render('admin/reversements/index', [
            'reversements' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
            'agences' => $agences,
        ]);
    }

    public function store(StoreReversementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Reversement::create([
            'agence_id' => $validated['agence_id'],
            'montant' => $validated['montant'],
            'periode' => $validated['periode'],
            'statut' => 'en_attente',
        ]);

        $agence = Agence::findOrFail($validated['agence_id']);

        return back()->with(
            'success',
            "Reversement de {$agence->nom} enregistré en attente de validation."
        );
    }

    public function effectuer(Request $request, Reversement $reversement): RedirectResponse
    {
        if ($reversement->statut !== 'en_attente') {
            return back()->with('error', 'Ce reversement a déjà été effectué.');
        }

        if (! AgenceSoldeService::peutReverser(
            $reversement->agence_id,
            (float) $reversement->montant,
            $reversement->id,
        )) {
            return back()->with(
                'error',
                'Le solde disponible de l\'agence est insuffisant pour effectuer ce reversement.',
            );
        }

        $reversement->update([
            'statut' => 'effectué',
            'admin_id' => $request->user()->id,
            'effectue_le' => Carbon::now(),
        ]);

        return back()->with(
            'success',
            "Reversement de {$reversement->agence->nom} effectué avec succès."
        );
    }
}
