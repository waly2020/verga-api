<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW vue_agences_paiements_valides AS
            SELECT
                a.id AS agence_id,
                COALESCE(paiements.montant_sous_total, 0) AS montant_sous_total,
                COALESCE(paiements.nb_paiements, 0) AS nb_paiements
            FROM agences a
            LEFT JOIN (
                SELECT
                    c.agence_id,
                    SUM(p.montant_sous_total) AS montant_sous_total,
                    COUNT(p.id) AS nb_paiements
                FROM paiements p
                INNER JOIN commandes c ON c.id = p.commande_id
                WHERE p.statut = 'validé'
                GROUP BY c.agence_id
            ) AS paiements ON paiements.agence_id = a.id
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW vue_agences_reversements AS
            SELECT
                a.id AS agence_id,
                COALESCE(reversements.montant, 0) AS montant,
                COALESCE(reversements.nb_reversements, 0) AS nb_reversements
            FROM agences a
            LEFT JOIN (
                SELECT
                    r.agence_id,
                    SUM(r.montant) AS montant,
                    COUNT(r.id) AS nb_reversements
                FROM reversements r
                WHERE r.statut = 'effectué'
                GROUP BY r.agence_id
            ) AS reversements ON reversements.agence_id = a.id
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW vue_agences_soldes AS
            SELECT
                p.agence_id,
                p.montant_sous_total - r.montant AS montant_solde,
                p.montant_sous_total AS montant_paiements_valides,
                r.montant AS montant_reversements
            FROM vue_agences_paiements_valides p
            INNER JOIN vue_agences_reversements r ON r.agence_id = p.agence_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vue_agences_soldes');
        DB::statement('DROP VIEW IF EXISTS vue_agences_reversements');
        DB::statement('DROP VIEW IF EXISTS vue_agences_paiements_valides');
    }
};
