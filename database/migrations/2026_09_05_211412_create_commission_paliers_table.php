<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_paliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('configuration_commission_id')
                ->constrained('configurations_commission')
                ->cascadeOnDelete();
            $table->decimal('montant_min', 12, 2);
            $table->decimal('montant_max', 12, 2)->nullable();
            $table->decimal('frais', 12, 2);
            $table->string('libelle')->nullable();
            $table->timestamps();

            $table->unique(['configuration_commission_id', 'montant_min'], 'commission_paliers_config_min_unique');
            $table->index('montant_min');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_paliers');
    }
};
