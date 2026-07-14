<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('documentable');
            $table->string('type_document');
            $table->string('chemin');
            $table->string('nom_original')->nullable();
            $table->timestamps();

            $table->index('type_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
