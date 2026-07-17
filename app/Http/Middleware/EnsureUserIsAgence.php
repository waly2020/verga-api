<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AgenceUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAgence
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof AgenceUser) {
            return response()->json([
                'message' => 'Accès réservé aux comptes agence.',
            ], 403);
        }

        $user->loadMissing('agence');

        if (! $user->isActif()) {
            return response()->json([
                'message' => 'Ce compte est suspendu.',
            ], 403);
        }

        if (! $user->agence) {
            return response()->json([
                'message' => 'Aucune agence associée à ce compte.',
            ], 403);
        }

        if ($user->agence->statut !== 'actif') {
            return response()->json([
                'message' => 'Ce compte agence est '.$user->agence->statut.'.',
            ], 403);
        }

        return $next($request);
    }
}
