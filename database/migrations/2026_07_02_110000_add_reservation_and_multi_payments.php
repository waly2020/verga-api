<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->decimal('quantite_payee', 10, 3)->default(0)->after('quantite');
            $table->boolean('capacite_bloquee')->default(false)->after('quantite_payee');
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->dropUnique(['commande_id']);
            $table->decimal('quantite', 10, 3)->nullable()->after('commande_id');
            $table->decimal('montant_sous_total', 12, 2)->nullable()->after('montant');
            $table->decimal('montant_commission_client', 12, 2)->default(0)->after('montant_sous_total');
            $table->index('commande_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE commandes DROP CONSTRAINT IF EXISTS commandes_statut_check');
            DB::statement("ALTER TABLE commandes ADD CONSTRAINT commandes_statut_check CHECK (statut IN ('en_attente', 'réservée', 'confirmée', 'annulée'))");
        }
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropIndex(['commande_id']);
            $table->dropColumn(['quantite', 'montant_sous_total', 'montant_commission_client']);
            $table->unique('commande_id');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['quantite_payee', 'capacite_bloquee']);
        });
    }
};
