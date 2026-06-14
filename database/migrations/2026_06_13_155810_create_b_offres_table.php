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
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('type', ['particulier', 'metre_cube', 'conteneur']);
            $table->decimal('prix', 12, 2);
            $table->string('origine');
            $table->string('destination');
            $table->enum('statut', ['active', 'inactive', 'archivée'])->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};
