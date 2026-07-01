<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class DashboardEndpoints
{
    #[OA\Get(
        path: '/client/dashboard',
        operationId: 'clientDashboard',
        summary: 'Statistiques du tableau de bord client',
        description: 'KPIs, répartitions par statut et dernières commandes pour le client connecté (particulier, entreprise ou boutique).',
        tags: ['Client - Dashboard'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'periode',
                description: 'Période de filtrage',
                schema: new OA\Schema(type: 'string', enum: ['mois', 'mois_dernier', 'trimestre', 'semestre', 'annee', 'tout'], default: 'mois')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statistiques client', content: new OA\JsonContent(ref: '#/components/schemas/ClientDashboardResponse')),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Période invalide'),
        ]
    )]
    public function clientDashboard(): void {}

    #[OA\Get(
        path: '/agence/dashboard',
        operationId: 'agenceDashboard',
        summary: 'Statistiques du tableau de bord agence',
        description: 'KPIs opérationnels et financiers pour l\'agence connectée (entreprise de transit).',
        tags: ['Agence - Dashboard'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'periode',
                description: 'Période de filtrage',
                schema: new OA\Schema(type: 'string', enum: ['mois', 'mois_dernier', 'trimestre', 'semestre', 'annee', 'tout'], default: 'mois')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statistiques agence', content: new OA\JsonContent(ref: '#/components/schemas/AgenceDashboardResponse')),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Période invalide'),
        ]
    )]
    public function agenceDashboard(): void {}
}
