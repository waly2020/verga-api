<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('types_offres', function (Blueprint $table) {
            $table->foreignUuid('agence_id')
                ->nullable()
                ->after('id')
                ->constrained('agences')
                ->cascadeOnDelete();

            $table->dropUnique(['slug']);
            $table->unique(['slug', 'agence_id']);
        });
    }

    public function down(): void
    {
        Schema::table('types_offres', function (Blueprint $table) {
            $table->dropUnique(['slug', 'agence_id']);
            $table->unique('slug');

            $table->dropConstrainedForeignId('agence_id');
        });
    }
};
