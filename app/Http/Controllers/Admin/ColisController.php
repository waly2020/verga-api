<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colis;
use App\Models\HistoriqueColis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ColisController extends Controller
{
    private const FLUX = [
        "déposé"   => "en_transit",
        "en_transit"          => "arrivé",
        "arrivé"         => "récupéré",
    ];

    public function index(Request $request): Response
    {
        $query = Colis::with([
            'commande:id,code',
            'agence:id,nom',
        ]);

        if ($search = $request->get('search')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/colis/index', [
            'colis'   => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function show(Colis $colis): Response
    {
        $colis->load([
            'agence:id,nom,email,ville',
            'commande:id,code,user_id,montant_total,statut',
            'commande.user:id,name,email',
            'historique' => fn ($q) => $q->with('user:id,name')->latest(),
        ]);

        return Inertia::render('admin/colis/show', [
            'colis'       => $colis,
            'next_statut' => self::FLUX[$colis->statut] ?? null,
        ]);
    }

    public function updateStatut(Request $request, Colis $colis): RedirectResponse
    {
        $next = self::FLUX[$colis->statut] ?? null;

        if (! $next) {
            return back()->with('error', 'Ce colis est dans son statut final.');
        }

        $colis->update(['statut' => $next]);

        HistoriqueColis::create([
            'colis_id'    => $colis->id,
            'user_id'     => $request->user()->id,
            'statut'      => $next,
            'commentaire' => $request->get('commentaire'),
        ]);

        $labels = [
            'en_transit'  => 'en transit',
            "arrivé" => "arrivé à destination",
            "récupéré" => "récupéré par le client",
        ];

        $label = $labels[$next] ?? $next;

        return back()->with('success', "Colis {$colis->reference} marqué comme {$label}.");
    }
}