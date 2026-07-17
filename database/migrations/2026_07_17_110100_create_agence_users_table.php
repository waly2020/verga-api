<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agence_users', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('agence_id')->constrained('agences')->cascadeOnDelete();
            $table->foreignUuid('agence_role_id')->constrained('agence_roles')->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telephone', 20)->nullable();
            $table->string('password');
            $table->string('statut')->default('actif');
            $table->boolean('est_proprietaire')->default(false);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['agence_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agence_users');
    }
};
