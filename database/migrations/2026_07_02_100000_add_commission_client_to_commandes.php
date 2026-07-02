<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->decimal('montant_sous_total', 12, 2)->nullable()->after('quantite');
            $table->decimal('montant_commission_client', 12, 2)->default(0)->after('montant_sous_total');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['montant_sous_total', 'montant_commission_client']);
        });
    }
};
