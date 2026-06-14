<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('commande_id')->unique()->constrained('commandes')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('methode');
            $table->string('reference')->nullable()->unique();
            $table->enum('statut', ['en_attente', 'validé', 'remboursé', 'échec'])->default('en_attente')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
