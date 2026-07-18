<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE offres DROP CONSTRAINT IF EXISTS offres_type_check');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE offres DROP CONSTRAINT IF EXISTS offres_type_check');
            DB::statement("ALTER TABLE offres ADD CONSTRAINT offres_type_check CHECK (type IN ('particulier', 'metre_cube', 'conteneur'))");
        }
    }
};
