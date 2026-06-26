<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignUuid('client_id')->after('id')->constrained('clients')->cascadeOnDelete();
        });

        Schema::table('avis', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'commande_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('avis', function (Blueprint $table) {
            $table->foreignUuid('client_id')->after('id')->constrained('clients')->cascadeOnDelete();
            $table->unique(['client_id', 'commande_id']);
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->foreignUuid('client_id')->nullable()->after('id')->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('avis', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'commande_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::table('avis', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'commande_id']);
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        });
    }
};
