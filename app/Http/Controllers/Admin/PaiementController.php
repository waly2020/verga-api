<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaiementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Paiement::with('commande:id,code');

        if ($search = $request->get('search')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/paiements/index', [
            'paiements' => $query->latest()->paginate(15)->withQueryString(),
            'filters'   => $request->only(['search', 'statut']),
        ]);
    }
}