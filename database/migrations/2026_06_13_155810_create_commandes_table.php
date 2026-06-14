<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('offre_id')->constrained('offres')->restrictOnDelete();
            $table->foreignUuid('agence_id')->constrained('agences')->restrictOnDelete();
            $table->string('code')->unique();
            $table->decimal('quantite', 10, 3);
            $table->decimal('montant_total', 12, 2);
            $table->enum('statut', ['en_attente', 'confirmée', 'annulée'])->default('en_attente')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
