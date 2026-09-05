<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ville_depart_id')->constrained('villes')->restrictOnDelete();
            $table->foreignUuid('ville_arrivee_id')->constrained('villes')->restrictOnDelete();
            $table->decimal('montant', 12, 2)->nullable();
            $table->decimal('commission_pourcentage', 5, 2)->nullable();
            $table->boolean('appliquer_configuration')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['ville_depart_id', 'ville_arrivee_id']);
        });

        Schema::create('agence_destination', function (Blueprint $table) {
            $table->foreignUuid('agence_id')->constrained('agences')->cascadeOnDelete();
            $table->foreignUuid('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['agence_id', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agence_destination');
        Schema::dropIfExists('destinations');
    }
};
