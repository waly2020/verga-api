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
#[OA\Tag(name: 'Client - Offres', description: 'Catalogue public des offres actives')]
#[OA\Tag(name: 'Client - Commandes', description: 'Commandes du client (création publique ou connectée)')]
#[OA\Tag(name: 'Client - Colis', description: 'Colis du client')]
#[OA\Tag(name: 'Client - Paiements', description: 'Paiements et vérification de statut')]
#[OA\Tag(name: 'Client - Réclamations', description: 'Réclamations du client')]
#[OA\Tag(name: 'Client - Dashboard', description: 'Statistiques tableau de bord client')]
#[OA\Tag(name: 'Paiement - Bamboo Pay', description: 'Webhooks et intégration Bamboo Pay')]
#[OA\Tag(name: 'Agence - Dashboard', description: 'Statistiques tableau de bord agence')]
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
#[OA\Schema(
    schema: 'CheckoutResponse',
    properties: [
        new OA\Property(property: 'commande_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'code', type: 'string', example: 'CMD-ABCDEFGH'),
        new OA\Property(property: 'montant_total', type: 'number', format: 'float', example: 25000),
        new OA\Property(property: 'paiement_code', type: 'string', example: 'PAY-ABCDEFGH'),
        new OA\Property(property: 'redirect_url', type: 'string', format: 'uri', example: 'https://devfront-bamboopay.ventis.group/pay/abc'),
        new OA\Property(property: 'verification_url', type: 'string', format: 'uri', example: 'http://localhost/api/v1/client/paiements/PAY-ABCDEFGH/statut'),
    ]
)]
#[OA\Schema(
    schema: 'PaymentStatusCheckResponse',
    properties: [
        new OA\Property(property: 'paiement_code', type: 'string', example: 'PAY-ABCDEFGH'),
        new OA\Property(property: 'statut', type: 'string', enum: ['en_attente', 'validé', 'échec', 'remboursé']),
        new OA\Property(property: 'bamboo_reference', type: 'string', nullable: true, example: 'TXN-2025-000381'),
        new OA\Property(property: 'commande_code', type: 'string', example: 'CMD-ABCDEFGH'),
        new OA\Property(property: 'commande_statut', type: 'string', enum: ['en_attente', 'confirmée', 'annulée']),
        new OA\Property(property: 'en_attente_bamboo', type: 'boolean', example: false),
    ]
)]
#[OA\Schema(
    schema: 'ClientDashboardResponse',
    properties: [
        new OA\Property(property: 'data', properties: [
            new OA\Property(property: 'periode', type: 'string', example: 'mois'),
            new OA\Property(property: 'debut', type: 'string', format: 'date-time'),
            new OA\Property(property: 'fin', type: 'string', format: 'date-time'),
            new OA\Property(property: 'profil', properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'entreprise', 'boutique']),
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'prenom', type: 'string'),
            ], type: 'object'),
            new OA\Property(property: 'stats', properties: [
                new OA\Property(property: 'nb_commandes', type: 'integer'),
                new OA\Property(property: 'nb_commandes_en_attente', type: 'integer'),
                new OA\Property(property: 'nb_commandes_confirmees', type: 'integer'),
                new OA\Property(property: 'nb_colis', type: 'integer'),
                new OA\Property(property: 'nb_colis_en_transit', type: 'integer'),
                new OA\Property(property: 'nb_colis_arrives', type: 'integer'),
                new OA\Property(property: 'total_depense', type: 'number', format: 'float'),
                new OA\Property(property: 'nb_reclamations', type: 'integer'),
                new OA\Property(property: 'nb_reclamations_ouvertes', type: 'integer'),
            ], type: 'object'),
            new OA\Property(property: 'commandes_par_statut', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
            new OA\Property(property: 'colis_par_statut', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
            new OA\Property(property: 'dernieres_commandes', type: 'array', items: new OA\Items(type: 'object')),
        ], type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'AgenceDashboardResponse',
    properties: [
        new OA\Property(property: 'data', properties: [
            new OA\Property(property: 'periode', type: 'string', example: 'mois'),
            new OA\Property(property: 'debut', type: 'string', format: 'date-time'),
            new OA\Property(property: 'fin', type: 'string', format: 'date-time'),
            new OA\Property(property: 'profil', properties: [
                new OA\Property(property: 'nom', type: 'string'),
                new OA\Property(property: 'ville', type: 'string', nullable: true),
                new OA\Property(property: 'statut', type: 'string'),
            ], type: 'object'),
            new OA\Property(property: 'stats', properties: [
                new OA\Property(property: 'nb_offres', type: 'integer'),
                new OA\Property(property: 'nb_offres_actives', type: 'integer'),
                new OA\Property(property: 'capacite_disponible_totale', type: 'number', format: 'float'),
                new OA\Property(property: 'nb_commandes', type: 'integer'),
                new OA\Property(property: 'nb_commandes_en_attente', type: 'integer'),
                new OA\Property(property: 'nb_commandes_confirmees', type: 'integer'),
                new OA\Property(property: 'total_paiements', type: 'number', format: 'float'),
                new OA\Property(property: 'total_commissions', type: 'number', format: 'float'),
                new OA\Property(property: 'revenu_net_estime', type: 'number', format: 'float'),
                new OA\Property(property: 'reversements_en_attente', type: 'number', format: 'float'),
                new OA\Property(property: 'nb_colis', type: 'integer'),
                new OA\Property(property: 'nb_colis_en_transit', type: 'integer'),
                new OA\Property(property: 'nb_reclamations_ouvertes', type: 'integer'),
            ], type: 'object'),
            new OA\Property(property: 'commandes_par_statut', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
            new OA\Property(property: 'colis_par_statut', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
            new OA\Property(property: 'top_offres', type: 'array', items: new OA\Items(type: 'object')),
            new OA\Property(property: 'dernieres_commandes', type: 'array', items: new OA\Items(type: 'object')),
        ], type: 'object'),
    ]
)]
class OpenApiSpec {}
