<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
            $table->foreignUuid('agence_id')->nullable()->constrained('agences')->nullOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone', 20);
            $table->string('email');
            $table->string('objet');
            $table->text('description');
            $table->enum('statut', ['ouverte', 'en_cours', 'résolue', 'fermée'])->default('ouverte')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};
