<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class PaymentEndpoints
{
    #[OA\Post(
        path: '/payments/bamboo-pay/callback',
        operationId: 'bambooPayCallback',
        summary: 'Webhook Bamboo Pay',
        description: 'Appelé par Bamboo Pay lors d\'un changement de statut. Déclenche le même traitement idempotent que la vérification de statut côté client (`PaymentSettlementService`).',
        tags: ['Paiement - Bamboo Pay'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'completed', 'failed'], example: 'completed'),
                    new OA\Property(property: 'billingId', type: 'string', description: 'Code paiement VERGA (`PAY-...`)', example: 'PAY-ABCDEFGH'),
                    new OA\Property(property: 'reference', type: 'string', description: 'Référence Bamboo Pay', example: 'TXN-2025-000381'),
                    new OA\Property(property: 'paymentType', type: 'string', nullable: true),
                    new OA\Property(property: 'merchantName', type: 'string', nullable: true),
                    new OA\Property(property: 'callbackUrl', type: 'string', nullable: true),
                    new OA\Property(property: 'returnUrl', type: 'string', nullable: true),
                    new OA\Property(property: 'createdAt', type: 'string', nullable: true),
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
