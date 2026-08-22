<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE publicites DROP CONSTRAINT IF EXISTS publicites_statut_check');
        DB::statement("ALTER TABLE publicites ADD CONSTRAINT publicites_statut_check CHECK (statut IN ('en_attente', 'validée', 'refusée', 'publiée', 'expirée', 'retirée'))");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("UPDATE publicites SET statut = 'expirée' WHERE statut = 'retirée'");
        DB::statement('ALTER TABLE publicites DROP CONSTRAINT IF EXISTS publicites_statut_check');
        DB::statement("ALTER TABLE publicites ADD CONSTRAINT publicites_statut_check CHECK (statut IN ('en_attente', 'validée', 'refusée', 'publiée', 'expirée'))");
    }
};
