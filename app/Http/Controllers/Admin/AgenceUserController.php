<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgenceUserRequest;
use App\Http\Requests\Admin\UpdateAgenceUserRequest;
use App\Models\Agence;
use App\Models\AgenceRole;
use App\Models\AgenceUser;
use App\Services\AgenceUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgenceUserController extends Controller
{
    public function __construct(
        private readonly AgenceUserService $users,
    ) {}

    public function index(Request $request): Response
    {
        $query = AgenceUser::query()
            ->with([
                'agence:id,nom',
                'role:id,slug,nom',
            ]);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%")
                    ->orWhereHas('agence', fn ($query) => $query->where('nom', 'like', "%{$search}%"))
                    ->orWhereHas('role', fn ($query) => $query->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut')->value());
        }

        return Inertia::render('admin/agence-users/index', [
            'users' => $query->latest()->paginate(15)->withQueryString(),
            'agences' => Agence::query()->orderBy('nom')->get(['id', 'nom']),
            'roles' => AgenceRole::query()
                ->where('actif', true)
                ->where('est_systeme', false)
                ->orderBy('nom')
                ->get(['id', 'slug', 'nom']),
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function store(StoreAgenceUserRequest $request): RedirectResponse
    {
        $agence = Agence::query()->findOrFail($request->validated('agence_id'));
        $user = $this->users->create($agence, $request->validated());

        return back()->with('success', "L'utilisateur « {$user->name} » a été créé.");
    }

    public function update(
        UpdateAgenceUserRequest $request,
        AgenceUser $agenceUser,
    ): RedirectResponse {
        $this->users->update($agenceUser, $request->validated());

        return back()->with('success', "L'utilisateur « {$agenceUser->name} » a été mis à jour.");
    }

    public function destroy(AgenceUser $agenceUser): RedirectResponse
    {
        $name = $agenceUser->name;
        $this->users->delete($agenceUser);

        return back()->with('success', "L'utilisateur « {$name} » a été supprimé.");
    }
}
