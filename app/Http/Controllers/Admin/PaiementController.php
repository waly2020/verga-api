<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Services\CommandeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class PaiementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Paiement::with('commande:id,code');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('bamboo_reference', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return Inertia::render('admin/paiements/index', [
            'paiements' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function verifierStatut(Paiement $paiement, CommandeCheckoutService $checkout): RedirectResponse
    {
        if (! $paiement->code) {
            return back()->with('error', 'Ce paiement ne possède pas de référence VERGA.');
        }

        try {
            $result = $checkout->verifyPaymentStatus($paiement->code);
        } catch (RequestException $exception) {
            return back()->with(
                'error',
                'Bamboo Pay n\'a pas pu confirmer le statut de cette transaction.'
            );
        } catch (Throwable) {
            return back()->with(
                'error',
                'Une erreur est survenue lors de la vérification du paiement.'
            );
        }

        [$flashKey, $message] = match ($result['statut']) {
            'validé' => ['success', 'Paiement validé avec succès.'],
            'échec' => ['error', filled($result['bamboo_message'] ?? null)
                ? 'Le paiement a échoué : '.$result['bamboo_message']
                : 'Le paiement a échoué côté Bamboo Pay.'],
            'remboursé' => ['error', 'Ce paiement a été remboursé.'],
            default => ['success', filled($result['bamboo_message'] ?? null)
                ? 'Statut vérifié : '.$result['bamboo_message']
                : 'Statut vérifié : le paiement est toujours en attente.'],
        };

        return back()->with($flashKey, $message);
    }
}
