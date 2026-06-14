<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_activites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('modele')->nullable();
            $table->string('modele_id')->nullable();
            $table->json('donnees')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['modele', 'modele_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_activites');
    }
};
