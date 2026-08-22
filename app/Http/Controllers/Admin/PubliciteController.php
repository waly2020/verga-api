<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefuserPubliciteRequest;
use App\Http\Requests\Admin\StorePubliciteRequest;
use App\Http\Requests\Admin\UpdatePubliciteStatutRequest;
use App\Models\Agence;
use App\Models\Client;
use App\Models\Offre;
use App\Models\Publicite;
use App\Services\PubliciteLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class PubliciteController extends Controller
{
    public function __construct(
        private readonly PubliciteLifecycleService $lifecycle,
    ) {}

    public function index(Request $request): Response
    {
        $this->lifecycle->expireOverdue();

        $query = Publicite::query()
            ->with(['agence:id,nom', 'client:id,nom,prenom', 'offre:id,titre']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/publicites/index', [
            'publicites' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
            'agences' => Agence::query()->where('statut', 'actif')->orderBy('nom')->get(['id', 'nom']),
            'clients' => Client::query()->where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            'offres' => Offre::query()->where('statut', 'active')->orderBy('titre')->get(['id', 'titre', 'agence_id']),
            'statut_transitions' => PubliciteLifecycleService::adminTransitionsMap(),
        ]);
    }

    public function store(StorePubliciteRequest $request): RedirectResponse
    {
        $image = $request->file('image');

        $this->lifecycle->createByAdmin(
            $request->validated(),
            $image instanceof UploadedFile ? $image : null,
        );

        return back()->with('success', 'La publicité a été créée et publiée.');
    }

    public function valider(Publicite $publicite): RedirectResponse
    {
        $this->lifecycle->valider($publicite);

        return back()->with('success', "La publicité « {$publicite->titre} » a été validée.");
    }

    public function refuser(RefuserPubliciteRequest $request, Publicite $publicite): RedirectResponse
    {
        $this->lifecycle->refuser($publicite, $request->validated('motif_refus'));

        return back()->with('success', "La publicité « {$publicite->titre} » a été refusée.");
    }

    public function updateStatut(UpdatePubliciteStatutRequest $request, Publicite $publicite): RedirectResponse
    {
        $this->lifecycle->updateStatutByAdmin(
            $publicite,
            $request->validated('statut'),
            $request->validated('motif'),
        );

        return back()->with('success', "Le statut de « {$publicite->titre} » a été mis à jour.");
    }
}
