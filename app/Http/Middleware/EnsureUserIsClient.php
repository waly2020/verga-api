<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isClient()) {
            return response()->json([
                'message' => 'Accès réservé aux comptes client.',
            ], 403);
        }

        if (! $user->client) {
            return response()->json([
                'message' => 'Aucun profil client associé à ce compte.',
            ], 403);
        }

        if ($user->client->statut !== 'actif') {
            return response()->json([
                'message' => 'Ce compte client est '.$user->client->statut.'.',
            ], 403);
        }

        return $next($request);
    }
}
