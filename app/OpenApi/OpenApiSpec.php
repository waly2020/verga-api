<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'VERGA API',
    description: 'API REST pour les applications externes VERGA (back-office agence Angular, application client mobile/web). Authentification Bearer Sanctum.',
    contact: new OA\Contact(name: 'VERGA', email: 'contact@verga.test')
)]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Token Sanctum obtenu via login ou register',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
#[OA\Tag(name: 'Agence - Auth', description: 'Connexion et session agence')]
#[OA\Tag(name: 'Agence - Offres', description: 'Gestion des offres de transport')]
#[OA\Tag(name: 'Agence - Commandes', description: 'Commandes reçues par l\'agence')]
#[OA\Tag(name: 'Agence - Colis', description: 'Suivi logistique des colis')]
#[OA\Tag(name: 'Agence - Réclamations', description: 'Litiges et réclamations')]
#[OA\Tag(name: 'Agence - Paiements', description: 'Transactions liées aux commandes')]
#[OA\Tag(name: 'Client - Auth', description: 'Inscription, connexion et session client')]
#[OA\Tag(name: 'Client - Profil', description: 'Mise à jour du profil client')]
#[OA\Tag(name: 'Client - Commandes', description: 'Commandes du client')]
#[OA\Tag(name: 'Client - Colis', description: 'Colis du client')]
#[OA\Tag(name: 'Client - Paiements', description: 'Paiements du client')]
#[OA\Tag(name: 'Client - Réclamations', description: 'Réclamations du client')]
#[OA\Schema(
    schema: 'MessageResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Opération réussie.'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: ['email' => ['Identifiants incorrects.']]
        ),
    ]
)]
#[OA\Schema(
    schema: 'TokenResponse',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '1|abcdef...'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 72),
    ]
)]
class OpenApiSpec {}
