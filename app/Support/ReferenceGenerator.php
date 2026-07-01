<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceGenerator
{
    public static function commande(): string
    {
        return self::unique('commandes', 'code', 'CMD');
    }

    public static function paiement(): string
    {
        return self::unique('paiements', 'code', 'PAY');
    }

    public static function colis(): string
    {
        return self::unique('colis', 'reference', 'COL');
    }

    private static function unique(string $table, string $column, string $prefix): string
    {
        do {
            $code = $prefix.'-'.strtoupper(Str::random(8));
        } while (DB::table($table)->where($column, $code)->exists());

        return $code;
    }
}
