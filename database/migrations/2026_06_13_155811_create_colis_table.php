<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignUuid('agence_id')->constrained('agences')->restrictOnDelete();
            $table->string('reference')->unique();
            $table->decimal('poids', 10, 3)->nullable();
            $table->decimal('volume', 10, 3)->nullable();
            $table->enum('statut', ['chez_client', 'déposé', 'en_transit', 'arrivé', 'récupéré'])->default('chez_client')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colis');
    }
};
