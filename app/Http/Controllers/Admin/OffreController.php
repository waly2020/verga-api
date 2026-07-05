<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\Offre;
use App\Models\TypeOffre;
use App\Services\OffreTypeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OffreController extends Controller
{
    public function __construct(
        private readonly OffreTypeResolver $typeResolver,
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agence_id' => ['required', 'uuid', 'exists:agences,id'],
            'titre' => ['required', 'string', 'max:255'],
            'type_offre_id' => ['required_without:type', 'uuid', 'exists:types_offres,id'],
            'type' => ['required_without:type_offre_id', Rule::in(['particulier', 'metre_cube', 'conteneur'])],
            'prix' => ['required', 'numeric', 'min:0'],
            'capacite_totale' => ['required', 'numeric', 'min:0.001'],
            'origine' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'statut' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'agence_id.required' => "L'agence est obligatoire.",
            'agence_id.exists' => "Cette agence n'existe pas.",
            'titre.required' => 'Le titre est obligatoire.',
            'type_offre_id.required_without' => 'Le type d\'offre est obligatoire.',
            'type.required_without' => 'Le type est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'origine.required' => "L'origine est obligatoire.",
            'destination.required' => 'La destination est obligatoire.',
        ]);

        $data = $this->typeResolver->resolveForCreate($validated);
        $data['capacite_disponible'] = $data['capacite_totale'];

        Offre::create($data);

        return back()->with('success', "L'offre \"{$validated['titre']}\" a été créée avec succès.");
    }
}
