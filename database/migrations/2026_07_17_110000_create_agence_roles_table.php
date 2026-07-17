<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agence_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('est_systeme')->default(false);
            $table->timestamps();

            $table->index('actif');
        });

        DB::table('agence_roles')->insert([
            [
                'id' => (string) Str::uuid(),
                'slug' => 'admin-agence',
                'nom' => 'Administrateur agence',
                'description' => 'Compte propriétaire de l’agence.',
                'actif' => true,
                'est_systeme' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agence_roles');
    }
};
