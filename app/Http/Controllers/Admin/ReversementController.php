<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reversement;
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

        return Inertia::render('admin/reversements/index', [
            'reversements' => $query->latest()->paginate(15)->withQueryString(),
            'filters'      => $request->only(['search', 'statut']),
        ]);
    }

    public function effectuer(Request $request, Reversement $reversement): RedirectResponse
    {
        if ($reversement->statut !== 'en_attente') {
            return back()->with('error', 'Ce reversement a déjà été effectué.');
        }

        $reversement->update([
            'statut'      => 'effectué',
            'admin_id'    => $request->user()->id,
            'effectue_le' => Carbon::now(),
        ]);

        return back()->with(
            'success',
            "Reversement de {$reversement->agence->nom} effectué avec succès."
        );
    }
}