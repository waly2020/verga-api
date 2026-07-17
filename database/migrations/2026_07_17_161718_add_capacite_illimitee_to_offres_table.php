<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->boolean('capacite_illimitee')->default(false)->after('prix');
            $table->decimal('capacite_totale', 12, 3)->nullable()->default(null)->change();
            $table->decimal('capacite_disponible', 12, 3)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropColumn('capacite_illimitee');
            $table->decimal('capacite_totale', 12, 3)->default(0)->nullable(false)->change();
            $table->decimal('capacite_disponible', 12, 3)->default(0)->nullable(false)->change();
        });
    }
};
