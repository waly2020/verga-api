<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('commandes', 'user_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (! Schema::hasColumn('commandes', 'client_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->foreignUuid('client_id')->after('id')->constrained('clients')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('avis', 'user_id')) {
            Schema::table('avis', function (Blueprint $table) {
                // MySQL : la FK user_id s'appuie sur l'index unique (user_id, commande_id).
                // Il faut supprimer la FK avant l'index unique.
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id', 'commande_id']);
                $table->dropColumn('user_id');
            });
        }

        if (! Schema::hasColumn('avis', 'client_id')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->foreignUuid('client_id')->after('id')->constrained('clients')->cascadeOnDelete();
                $table->unique(['client_id', 'commande_id']);
            });
        }

        if (Schema::hasColumn('reclamations', 'user_id')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (! Schema::hasColumn('reclamations', 'client_id')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->foreignUuid('client_id')->nullable()->after('id')->constrained('clients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reclamations', 'client_id')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            });
        }

        if (! Schema::hasColumn('reclamations', 'user_id')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('avis', 'client_id')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->dropUnique(['client_id', 'commande_id']);
                $table->dropColumn('client_id');
            });
        }

        if (! Schema::hasColumn('avis', 'user_id')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unique(['user_id', 'commande_id']);
            });
        }

        if (Schema::hasColumn('commandes', 'client_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            });
        }

        if (! Schema::hasColumn('commandes', 'user_id')) {
            Schema::table('commandes', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            });
        }
    }
};
