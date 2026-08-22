<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations_publicite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('prix_par_jour');
            $table->enum('type_frais', ['fixe', 'pourcentage']);
            $table->unsignedInteger('valeur_frais');
            $table->boolean('actif')->default(true)->index();
            $table->string('libelle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations_publicite');
    }
};
