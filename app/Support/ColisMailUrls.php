<?php

namespace App\Support;

use App\Models\Colis;

class ColisMailUrls
{
    public static function clientColis(Colis $colis): string
    {
        $base = rtrim((string) config('verga.frontend.client_url'), '/');

        return "{$base}/colis/{$colis->id}";
    }
}
