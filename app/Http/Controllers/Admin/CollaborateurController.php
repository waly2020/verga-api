<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class CollaborateurController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::whereIn('role', ['admin', 'collaborateur']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('admin/collaborateurs/index', [
            'collaborateurs' => $query->latest()->paginate(15)->withQueryString(),
            'filters'        => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/collaborateurs/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role'     => ['required', Rule::in(['admin', 'collaborateur'])],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'     => 'Le nom est obligatoire.',
            'email.required'    => "L'adresse email est obligatoire.",
            'email.email'       => "L'adresse email n'est pas valide.",
            'email.unique'      => 'Cette adresse email est déjà utilisée.',
            'role.required'     => 'Le rôle est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        User::create($validated);

        return redirect()
            ->route('admin.collaborateurs.index')
            ->with('success', "Le compte de {$validated['name']} a été créé avec succès.");
    }

    public function destroy(Request $request, User $collaborateur): RedirectResponse
    {
        if ($collaborateur->id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $collaborateur->name;
        $collaborateur->delete();

        return back()->with('success', "Le compte de {$name} a été supprimé.");
    }
}