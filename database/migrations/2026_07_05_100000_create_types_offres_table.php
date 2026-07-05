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
        Schema::create('types_offres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('nom');
            $table->string('description')->nullable();
            $table->string('unite');
            $table->string('unite_label');
            $table->boolean('quantite_entier')->default(false);
            $table->decimal('quantite_min', 10, 3)->default(0.001);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        $now = now();
        $types = [
            [
                'id' => (string) Str::uuid(),
                'slug' => 'particulier',
                'nom' => 'Particulier (au kg)',
                'description' => 'Transport au kilogramme pour envois légers',
                'unite' => 'kg',
                'unite_label' => 'au kg',
                'quantite_entier' => false,
                'quantite_min' => 0.001,
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'slug' => 'metre_cube',
                'nom' => 'Mètre cube',
                'description' => 'Transport au mètre cube',
                'unite' => 'm3',
                'unite_label' => 'au m³',
                'quantite_entier' => false,
                'quantite_min' => 0.001,
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'slug' => 'conteneur',
                'nom' => 'Conteneur',
                'description' => 'Transport par conteneur',
                'unite' => 'conteneur',
                'unite_label' => 'par conteneur',
                'quantite_entier' => true,
                'quantite_min' => 1,
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('types_offres')->insert($types);

        Schema::table('offres', function (Blueprint $table) {
            $table->foreignUuid('type_offre_id')
                ->nullable()
                ->after('type')
                ->constrained('types_offres')
                ->nullOnDelete();
        });

        foreach ($types as $type) {
            DB::table('offres')
                ->where('type', $type['slug'])
                ->update(['type_offre_id' => $type['id']]);
        }
    }

    public function down(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('type_offre_id');
        });

        Schema::dropIfExists('types_offres');
    }
};
