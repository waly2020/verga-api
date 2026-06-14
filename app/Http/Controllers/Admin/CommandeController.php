<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommandeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Commande::with([
            'user:id,name',
            'agence:id,nom',
        ]);

        if ($search = $request->get('search')) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/commandes/index', [
            'commandes' => $query->latest()->paginate(15)->withQueryString(),
            'filters'   => $request->only(['search', 'statut']),
        ]);
    }
}