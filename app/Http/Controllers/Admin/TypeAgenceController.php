<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypeAgence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TypeAgenceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:type_agences,nom'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'nom.required' => 'Le nom du type est obligatoire.',
            'nom.unique' => 'Un type avec ce nom existe déjà.',
        ]);

        TypeAgence::create($validated);

        return back()->with('success', "Type d'agence \"{$validated['nom']}\" créé avec succès.");
    }
}
