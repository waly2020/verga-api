<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReclamationController extends Controller
{
    private const TRANSITIONS = [
        'ouverte'  => ['en_cours', 'fermée'],
        'en_cours' => ['résolue', 'fermée'],
    ];

    public function index(Request $request): Response
    {
        $query = Reclamation::with('agence:id,nom');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('objet', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/reclamations/index', [
            'reclamations' => $query->latest()->paginate(15)->withQueryString(),
            'filters'      => $request->only(['search', 'statut']),
        ]);
    }

    public function show(Reclamation $reclamation): Response
    {
        $reclamation->load([
            'agence:id,nom,email,ville',
            'commande:id,code,montant_total,statut',
        ]);

        return Inertia::render('admin/reclamations/show', [
            'reclamation' => $reclamation,
            'transitions' => self::TRANSITIONS[$reclamation->statut] ?? [],
        ]);
    }

    public function updateStatut(Request $request, Reclamation $reclamation): RedirectResponse
    {
        $allowed = self::TRANSITIONS[$reclamation->statut] ?? [];

        if (empty($allowed)) {
            return back()->with('error', 'Cette réclamation est déjà dans un état final.');
        }

        $validated = $request->validate([
            'statut' => ['required', Rule::in($allowed)],
        ]);

        $reclamation->update(['statut' => $validated['statut']]);

        $labels = [
            'en_cours' => 'prise en charge',
            'résolue'  => 'résolue',
            'fermée'   => 'fermée',
        ];

        return back()->with(
            'success',
            "Réclamation de {$reclamation->nom} marquée comme {$labels[$validated['statut']]}."
        );
    }
}