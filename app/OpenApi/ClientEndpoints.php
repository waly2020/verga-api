<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ClientEndpoints
{
    #[OA\Post(
        path: '/client/register',
        operationId: 'clientRegister',
        summary: 'Inscription client',
        description: 'Crée un compte utilisateur (`role=client`) et un profil `clients`. Seule voie de création de compte client (non disponible dans le back-office admin).',
        tags: ['Client - Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nom', 'prenom', 'email', 'password', 'password_confirmation', 'telephone'],
                properties: [
                    new OA\Property(property: 'nom', type: 'string', example: 'Obame'),
                    new OA\Property(property: 'prenom', type: 'string', example: 'Sarah'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'sarah@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                    new OA\Property(property: 'telephone', type: 'string', example: '0622222222'),
                    new OA\Property(property: 'adresse', type: 'string', nullable: true),
                    new OA\Property(property: 'ville', type: 'string', nullable: true),
                    new OA\Property(property: 'pays', type: 'string', example: 'Gabon', nullable: true),
                    new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'entreprise', 'boutique'], default: 'particulier'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'mobile-app'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé + token', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
            new OA\Response(response: 422, description: 'Validation échouée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Trop de tentatives'),
        ]
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/client/login',
        operationId: 'clientLogin',
        summary: 'Connexion client',
        tags: ['Client - Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'device_name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Connexion réussie', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
            new OA\Response(response: 403, description: 'Compte bloqué'),
            new OA\Response(response: 422, description: 'Identifiants invalides'),
        ]
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/client/logout',
        operationId: 'clientLogout',
        summary: 'Déconnexion client',
        tags: ['Client - Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token révoqué', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function logout(): void {}

    #[OA\Get(
        path: '/client/me',
        operationId: 'clientMe',
        summary: 'Profil client connecté',
        tags: ['Client - Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profil utilisateur et client'),
        ]
    )]
    public function me(): void {}

    #[OA\Put(
        path: '/client/profile',
        operationId: 'clientUpdateProfile',
        summary: 'Mettre à jour le profil client',
        tags: ['Client - Profil'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom', type: 'string'),
                    new OA\Property(property: 'prenom', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'telephone', type: 'string'),
                    new OA\Property(property: 'adresse', type: 'string', nullable: true),
                    new OA\Property(property: 'ville', type: 'string', nullable: true),
                    new OA\Property(property: 'pays', type: 'string', nullable: true),
                    new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'entreprise', 'boutique']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profil mis à jour'),
            new OA\Response(response: 422, description: 'Validation échouée'),
        ]
    )]
    public function updateProfile(): void {}

    #[OA\Put(
        path: '/client/password',
        operationId: 'clientUpdatePassword',
        summary: 'Modifier le mot de passe client',
        tags: ['Client - Profil'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mot de passe mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function updatePassword(): void {}

    #[OA\Get(
        path: '/client/commandes',
        operationId: 'clientListCommandes',
        summary: 'Lister mes commandes',
        tags: ['Client - Commandes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Recherche par code', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'confirmée', 'annulée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée des commandes du client'),
        ]
    )]
    public function listCommandes(): void {}

    #[OA\Get(
        path: '/client/commandes/{commande}',
        operationId: 'clientShowCommande',
        summary: 'Détail d\'une commande',
        tags: ['Client - Commandes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'commande', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail avec agence, offre, paiement, colis'),
            new OA\Response(response: 404, description: 'Commande introuvable'),
        ]
    )]
    public function showCommande(): void {}

    #[OA\Get(
        path: '/client/colis',
        operationId: 'clientListColis',
        summary: 'Lister mes colis',
        tags: ['Client - Colis'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['déposé', 'en_transit', 'arrivé', 'récupéré'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée'),
        ]
    )]
    public function listColis(): void {}

    #[OA\Get(
        path: '/client/colis/{colis}',
        operationId: 'clientShowColis',
        summary: 'Détail d\'un colis',
        tags: ['Client - Colis'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'colis', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail avec historique'),
        ]
    )]
    public function showColis(): void {}

    #[OA\Get(
        path: '/client/paiements',
        operationId: 'clientListPaiements',
        summary: 'Lister mes paiements',
        tags: ['Client - Paiements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'validé', 'remboursé', 'échec'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée'),
        ]
    )]
    public function listPaiements(): void {}

    #[OA\Get(
        path: '/client/reclamations',
        operationId: 'clientListReclamations',
        summary: 'Lister mes réclamations',
        tags: ['Client - Réclamations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['ouverte', 'en_cours', 'résolue', 'fermée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée'),
        ]
    )]
    public function listReclamations(): void {}

    #[OA\Post(
        path: '/client/reclamations',
        operationId: 'clientCreateReclamation',
        summary: 'Créer une réclamation',
        description: 'Les infos client (nom, email…) sont remplies automatiquement depuis le profil connecté.',
        tags: ['Client - Réclamations'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['objet', 'description'],
                properties: [
                    new OA\Property(property: 'commande_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'agence_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'objet', type: 'string', example: 'Retard livraison'),
                    new OA\Property(property: 'description', type: 'string', example: 'Mon colis est en retard.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Réclamation créée'),
            new OA\Response(response: 422, description: 'Commande non autorisée'),
        ]
    )]
    public function createReclamation(): void {}

    #[OA\Get(
        path: '/client/reclamations/{reclamation}',
        operationId: 'clientShowReclamation',
        summary: 'Détail d\'une réclamation',
        tags: ['Client - Réclamations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'reclamation', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail réclamation'),
        ]
    )]
    public function showReclamation(): void {}
}
