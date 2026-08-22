<?php

namespace App\Support;

use App\Models\Reclamation;

class ReclamationMailUrls
{
    public static function adminShow(Reclamation $reclamation): string
    {
        return route('admin.reclamations.show', $reclamation);
    }

    public static function agenceShow(Reclamation $reclamation): string
    {
        $base = rtrim((string) config('verga.frontend.agence_url'), '/');

        return "{$base}/reclamations/{$reclamation->id}";
    }

    public static function clientShow(Reclamation $reclamation): string
    {
        $base = rtrim((string) config('verga.frontend.client_url'), '/');

        return "{$base}/reclamations/{$reclamation->id}";
    }
}
