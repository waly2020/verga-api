<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE colis DROP CONSTRAINT IF EXISTS colis_statut_check');
            DB::statement("ALTER TABLE colis ADD CONSTRAINT colis_statut_check CHECK (statut IN ('chez_client', 'déposé', 'en_transit', 'arrivé', 'récupéré'))");
            DB::statement("ALTER TABLE colis ALTER COLUMN statut SET DEFAULT 'chez_client'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE colis DROP CONSTRAINT IF EXISTS colis_statut_check');
            DB::statement("ALTER TABLE colis ADD CONSTRAINT colis_statut_check CHECK (statut IN ('déposé', 'en_transit', 'arrivé', 'récupéré'))");
            DB::statement("ALTER TABLE colis ALTER COLUMN statut SET DEFAULT 'déposé'");
        }
    }
};
