<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->decimal('montant_commission_agence', 12, 2)
                ->nullable()
                ->after('montant_commission_client');
            $table->decimal('montant_agence', 12, 2)
                ->nullable()
                ->after('montant_commission_agence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn([
                'montant_commission_agence',
                'montant_agence',
            ]);
        });
    }
};
