<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class PaymentEndpoints
{
    #[OA\Post(
        path: '/payments/bamboo-pay/callback',
        operationId: 'bambooPayCallback',
        summary: 'Webhook Bamboo Pay',
        description: 'Webhook Bamboo Pay — traite chaque versement de façon idempotente.

**Paiement partiel (réservation)** : `completed` → commande `réservée`, stock bloqué sur `quantite_reservee`, `quantite_payee` incrémentée.

**Solde payé** : `completed` → commande `confirmée` si entièrement réglée.

**Échec** : annule la commande seulement si aucun paiement validé ; une commande `réservée` reste réservée si le solde échoue.

**Payload v2 Bamboo** : `reference` = code marchand VERGA (`PAY-…`), `billingId` = référence Bamboo. Compatibilité conservée avec l\'ancien mapping (`billingId` = code VERGA).',
        tags: ['Paiement - Bamboo Pay'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'billingId', type: 'string', description: 'Référence transaction Bamboo Pay (nouveau format). Ancien format : code paiement VERGA.', example: 'TXN-2025-000381'),
                    new OA\Property(property: 'reference', type: 'string', description: 'Référence marchand VERGA (`PAY-…`). Ancien format : référence Bamboo.', example: 'PAY-ABCDEFGH'),
                    new OA\Property(property: 'numCpte', type: 'string', nullable: true, description: 'Numéro du payeur', example: '0612345678'),
                    new OA\Property(property: 'amount', type: 'number', format: 'double', nullable: true, description: 'Montant de la transaction', example: 26250),
                    new OA\Property(property: 'payername', type: 'string', nullable: true, description: 'Nom du payeur', example: 'Jean Mbaye'),
                    new OA\Property(property: 'status', type: 'string', enum: ['completed', 'failed'], example: 'completed'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true, description: 'Raison de succès ou d\'échec', example: 'Solde insuffisant'),
                    new OA\Property(property: 'paymentType', type: 'string', nullable: true, description: 'Moyen de paiement', example: 'moov_money'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Description de succès ou d\'échec', example: 'Transaction refusée par l\'opérateur'),
                    new OA\Property(property: 'idempotency_key', type: 'string', nullable: true, description: 'Identifiant unique du callback Bamboo', example: 'cbk-9f3a2c1e'),
                    new OA\Property(property: 'observation', type: 'string', nullable: true, description: 'Legacy — préférer `description` / `reason`', example: 'Solde insuffisant'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification reçue',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'received', type: 'boolean', example: true),
                        new OA\Property(property: 'processed', type: 'boolean', example: true),
                        new OA\Property(property: 'paiement_code', type: 'string', example: 'PAY-ABCDEFGH'),
                        new OA\Property(property: 'statut', type: 'string', example: 'validé'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function callback(): void {}
}
