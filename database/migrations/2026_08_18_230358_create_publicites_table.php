<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agence_id')->nullable()->constrained('agences')->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('offre_id')->nullable()->constrained('offres')->nullOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('lien')->nullable();
            $table->string('image_chemin')->nullable();
            $table->string('image_nom_original')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedInteger('nombre_jours');
            $table->enum('statut', ['en_attente', 'validée', 'refusée', 'publiée', 'expirée', 'retirée'])
                ->default('en_attente')
                ->index();
            $table->enum('statut_paiement', ['non_payé', 'en_attente', 'payé', 'échec'])
                ->default('non_payé')
                ->index();
            $table->text('motif_refus')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicites');
    }
};
