<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('paiements')
            ->where('statut', 'validé')
            ->whereNull('montant_agence')
            ->update([
                'montant_commission_agence' => 0,
                'montant_agence' => DB::raw('montant_sous_total'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La reprise historique est conservée pour ne pas effacer des montants financiers.
    }
};
