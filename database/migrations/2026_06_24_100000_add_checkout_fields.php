<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->decimal('capacite_totale', 12, 3)->default(0)->after('prix');
            $table->decimal('capacite_disponible', 12, 3)->default(0)->after('capacite_totale');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->change();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->string('nom')->nullable()->after('client_id');
            $table->string('prenom')->nullable()->after('nom');
            $table->string('telephone', 20)->nullable()->after('prenom');
        });

        Schema::table('colis', function (Blueprint $table) {
            $table->text('description')->nullable()->after('reference');
        });

        Schema::table('paiements', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('commande_id');
            $table->string('bamboo_reference')->nullable()->unique()->after('reference');
        });

        Schema::create('colis_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('colis_id')->constrained('colis')->cascadeOnDelete();
            $table->string('chemin');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colis_photos');

        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['code', 'bamboo_reference']);
        });

        Schema::table('colis', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['nom', 'prenom', 'telephone']);
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->uuid('client_id')->nullable(false)->change();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });

        Schema::table('offres', function (Blueprint $table) {
            $table->dropColumn(['capacite_totale', 'capacite_disponible']);
        });
    }
};
