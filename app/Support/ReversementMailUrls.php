<?php

namespace App\Support;

class ReversementMailUrls
{
    public static function agenceIndex(): string
    {
        $base = rtrim((string) config('verga.frontend.agence_url'), '/');

        return "{$base}/reversements";
    }
}
