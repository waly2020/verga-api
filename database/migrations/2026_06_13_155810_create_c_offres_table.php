<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agence_id')->constrained('agences')->cascadeOnDelete();
            $table->foreignUuid('destination_id')->constrained('destinations')->restrictOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('type', ['particulier', 'metre_cube', 'conteneur']);
            $table->decimal('prix', 12, 2);
            $table->boolean('capacite_illimitee')->default(false);
            $table->decimal('capacite_totale', 12, 3)->nullable();
            $table->decimal('capacite_disponible', 12, 3)->nullable();
            $table->date('date_depart')->nullable();
            $table->date('date_depot_colis')->nullable();
            $table->enum('statut', ['active', 'inactive', 'archivée'])->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};
