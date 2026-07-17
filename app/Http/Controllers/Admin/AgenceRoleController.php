<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgenceRoleRequest;
use App\Http\Requests\Admin\UpdateAgenceRoleRequest;
use App\Models\AgenceRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgenceRoleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AgenceRole::query()->withCount('users');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        return Inertia::render('admin/agence-roles/index', [
            'roles' => $query->orderBy('nom')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'actif']),
        ]);
    }

    public function store(StoreAgenceRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['actif'] = $request->boolean('actif', true);
        $validated['est_systeme'] = false;

        AgenceRole::create($validated);

        return back()->with('success', "Rôle « {$validated['nom']} » créé avec succès.");
    }

    public function update(UpdateAgenceRoleRequest $request, AgenceRole $agenceRole): RedirectResponse
    {
        if ($agenceRole->isSystem() && $request->hasAny(['slug', 'actif'])) {
            return back()->with('error', 'Le rôle système administrateur agence ne peut pas être modifié.');
        }

        $validated = $request->validated();

        if (array_key_exists('actif', $validated)) {
            $validated['actif'] = $request->boolean('actif');
        }

        $agenceRole->update($validated);

        return back()->with('success', "Rôle « {$agenceRole->nom} » mis à jour.");
    }

    public function destroy(AgenceRole $agenceRole): RedirectResponse
    {
        if ($agenceRole->isSystem()) {
            return back()->with('error', 'Le rôle système administrateur agence ne peut pas être supprimé.');
        }

        if ($agenceRole->users()->exists()) {
            return back()->with('error', 'Impossible de supprimer un rôle affecté à des utilisateurs.');
        }

        $nom = $agenceRole->nom;
        $agenceRole->delete();

        return back()->with('success', "Rôle « {$nom} » supprimé.");
    }
}
