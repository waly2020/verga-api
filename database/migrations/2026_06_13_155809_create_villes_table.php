<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pays');
            $table->string('ville');
            $table->string('code', 32);
            $table->boolean('actif')->default(true)->index();
            $table->timestamps();

            $table->unique('code');
            $table->unique(['pays', 'ville']);
            $table->index('pays');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villes');
    }
};
