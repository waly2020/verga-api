<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE commandes MODIFY statut ENUM('en_attente', 'réservée', 'confirmée', 'annulée') NOT NULL DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('commandes')->where('statut', 'réservée')->update(['statut' => 'en_attente']);

        DB::statement("ALTER TABLE commandes MODIFY statut ENUM('en_attente', 'confirmée', 'annulée') NOT NULL DEFAULT 'en_attente'");
    }
};
