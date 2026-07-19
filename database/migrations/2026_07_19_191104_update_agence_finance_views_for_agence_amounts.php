<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropPaymentViews();

        DB::statement(<<<'SQL'
            CREATE VIEW vue_agences_paiements_valides AS
            SELECT
                a.id AS agence_id,
                COALESCE(paiements.montant_sous_total, 0) AS montant_sous_total,
                COALESCE(paiements.montant_commission_agence, 0) AS montant_commission_agence,
                COALESCE(paiements.montant_agence, 0) AS montant_agence,
                COALESCE(paiements.nb_paiements, 0) AS nb_paiements
            FROM agences a
            LEFT JOIN (
                SELECT
                    c.agence_id,
                    SUM(COALESCE(p.montant_sous_total, 0)) AS montant_sous_total,
                    SUM(COALESCE(p.montant_commission_agence, 0)) AS montant_commission_agence,
                    SUM(COALESCE(p.montant_agence, p.montant_sous_total, 0)) AS montant_agence,
                    COUNT(p.id) AS nb_paiements
                FROM paiements p
                INNER JOIN commandes c ON c.id = p.commande_id
                WHERE p.statut = 'validé'
                GROUP BY c.agence_id
            ) AS paiements ON paiements.agence_id = a.id
        SQL);

        $this->createSoldesView('montant_agence');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropPaymentViews();

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

        $this->createSoldesView('montant_sous_total');
    }

    private function dropPaymentViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS vue_agences_soldes');
        DB::statement('DROP VIEW IF EXISTS vue_agences_paiements_valides');
    }

    private function createSoldesView(string $paymentColumn): void
    {
        DB::statement(<<<SQL
            CREATE VIEW vue_agences_soldes AS
            SELECT
                p.agence_id,
                p.{$paymentColumn} - r.montant AS montant_solde,
                p.{$paymentColumn} AS montant_paiements_valides,
                r.montant AS montant_reversements
            FROM vue_agences_paiements_valides p
            INNER JOIN vue_agences_reversements r ON r.agence_id = p.agence_id
        SQL);
    }
};
