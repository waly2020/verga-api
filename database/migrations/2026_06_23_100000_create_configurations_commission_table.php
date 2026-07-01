<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations_commission', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('destinataire', ['agence', 'client'])->unique();
            $table->enum('type', ['fixe', 'pourcentage']);
            $table->decimal('valeur', 12, 2);
            $table->boolean('actif')->default(true)->index();
            $table->string('libelle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations_commission');
    }
};
