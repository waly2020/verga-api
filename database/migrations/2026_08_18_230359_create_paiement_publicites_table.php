<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_publicite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publicite_id')->constrained('publicites')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('nombre_jours');
            $table->unsignedInteger('prix_par_jour');
            $table->unsignedInteger('montant_sous_total');
            $table->unsignedInteger('montant_frais')->default(0);
            $table->unsignedInteger('montant');
            $table->string('methode')->default('bamboo_redirect');
            $table->string('operateur')->nullable();
            $table->string('reference')->nullable()->unique();
            $table->string('bamboo_reference')->nullable()->unique();
            $table->text('bamboo_message')->nullable();
            $table->enum('statut', ['en_attente', 'validé', 'échec'])->default('en_attente')->index();
            $table->timestamps();

            $table->index('publicite_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_publicite');
    }
};
