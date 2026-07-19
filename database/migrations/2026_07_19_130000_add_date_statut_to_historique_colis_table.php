<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historique_colis', function (Blueprint $table) {
            $table->date('date_statut')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('historique_colis', function (Blueprint $table) {
            $table->dropColumn('date_statut');
        });
    }
};
