<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('agence_id')->constrained('agences')->cascadeOnDelete();
            $table->foreignUuid('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
            $table->tinyInteger('note')->unsigned();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'commande_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
