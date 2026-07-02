<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class ClientEndpoints
{
    #[OA\Get(
        path: '/client/offres',
        operationId: 'clientListOffres',
        summary: 'Lister les offres disponibles (paginé)',
        description: 'Catalogue public des offres actives avec capacité disponible > 0. Aucune authentification requise.

Filtres disponibles :
- `search` : titre, origine, destination, description
- `destination` : filtre sur la destination (partiel)
- `type` : particulier, metre_cube, conteneur
- `date_debut` / `date_fin` : plage de dates de publication (`created_at`, format `YYYY-MM-DD`)
- `page` / `per_page` : pagination (défaut 15, max 100)',
        tags: ['Client - Offres'],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Recherche titre, origine, destination, description', schema: new OA\Schema(type: 'string', example: 'Paris')),
            new OA\QueryParameter(name: 'destination', description: 'Filtre destination (correspondance partielle)', schema: new OA\Schema(type: 'string', example: 'Libreville')),
            new OA\QueryParameter(name: 'type', schema: new OA\Schema(type: 'string', enum: ['particulier', 'metre_cube', 'conteneur'])),
            new OA\QueryParameter(name: 'date_debut', description: 'Date de publication minimum (inclus)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-01')),
            new OA\QueryParameter(name: 'date_fin', description: 'Date de publication maximum (inclus)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des offres actives',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ClientOffreResource')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Filtres invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function listOffres(): void {}

    #[OA\Get(
        path: '/client/offres/{offre}/estimation',
        operationId: 'clientEstimateOffrePricing',
        summary: 'Estimer le montant à payer (prix + commission client)',
        description: 'Calcule le détail tarifaire avant checkout pour affichage front.

Retourne le sous-total (prix × quantité), la commission client active et le total à payer via Bamboo. Public, sans authentification.

**Exemple** : offre à 2 500 FCFA/kg, quantité 10, commission 5 % → sous-total 25 000, commission 1 250, total 26 250 FCFA.',
        tags: ['Client - Offres'],
        parameters: [
            new OA\PathParameter(name: 'offre', description: 'UUID de l\'offre active', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\QueryParameter(name: 'quantite', required: true, description: 'Quantité à estimer (kg, m³ ou conteneurs)', schema: new OA\Schema(type: 'number', format: 'float', example: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estimation tarifaire', content: new OA\JsonContent(ref: '#/components/schemas/OffrePricingEstimateResponse')),
            new OA\Response(response: 404, description: 'Offre introuvable ou inactive'),
            new OA\Response(response: 422, description: 'Quantité invalide', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function estimateOffrePricing(): void {}

    #[OA\Post(
        path: '/client/commandes',
        operationId: 'clientCreateCommande',
        summary: 'Créer une commande et initier le paiement',
        description: 'Crée en une seule requête : commande, colis (description + photos), paiement en attente, puis initie Bamboo Pay (redirection).

**Paiement total (comportement classique)** : omettre `quantite_reservee` ou la définir égale à `quantite`.

**Réservation partielle** : `quantite_reservee` > `quantite`
- Exemple : réserver 50 kg, payer 30 kg maintenant → `quantite_reservee: 50`, `quantite: 30`
- Après validation Bamboo : commande `réservée`, stock bloqué de 50 kg
- Solde : `POST /client/commandes/{id}/paiements` avec la quantité restante

Auth optionnelle : token Bearer = commande liée au compte ; sinon invité (nom/prénom obligatoires).',
        tags: ['Client - Commandes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'multipart/form-data' => new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['offre_id', 'quantite', 'telephone'],
                        properties: [
                            new OA\Property(property: 'offre_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'quantite', type: 'number', format: 'float', example: 30, description: 'Quantité payée lors de ce checkout (≤ quantite_reservee)'),
                            new OA\Property(property: 'quantite_reservee', type: 'number', format: 'float', example: 50, description: 'Quantité totale réservée (défaut = quantite). Si > quantite → mode réservation.'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Vêtements et chaussures'),
                            new OA\Property(property: 'photos', type: 'array', items: new OA\Items(type: 'string', format: 'binary'), maxItems: 5),
                            new OA\Property(property: 'nom', type: 'string', description: 'Obligatoire si invité (sans token)', example: 'Obame'),
                            new OA\Property(property: 'prenom', type: 'string', description: 'Obligatoire si invité (sans token)', example: 'Sarah'),
                            new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
                        ]
                    )
                ),
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        required: ['offre_id', 'quantite', 'telephone', 'nom', 'prenom'],
                        properties: [
                            new OA\Property(property: 'offre_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'quantite', type: 'number', format: 'float', example: 30, description: 'Quantité payée lors de ce checkout (≤ quantite_reservee)'),
                            new OA\Property(property: 'quantite_reservee', type: 'number', format: 'float', example: 50, description: 'Quantité totale réservée (défaut = quantite)'),
                            new OA\Property(property: 'description', type: 'string', nullable: true),
                            new OA\Property(property: 'nom', type: 'string'),
                            new OA\Property(property: 'prenom', type: 'string'),
                            new OA\Property(property: 'telephone', type: 'string'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 201, description: 'Commande créée + liens de paiement', content: new OA\JsonContent(ref: '#/components/schemas/CheckoutResponse')),
            new OA\Response(response: 422, description: 'Validation échouée ou stock insuffisant', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function createCommande(): void {}

    #[OA\Get(
        path: '/client/paiements/{code}/statut',
        operationId: 'clientCheckPaymentStatus',
        summary: 'Vérifier le statut d\'un paiement',
        description: 'Interroge Bamboo Pay si nécessaire et applique le règlement idempotent (même logique que le webhook).

Retourne aussi `quantite_reservee`, `quantite_payee` et `quantite_restante` pour suivre une réservation partielle.',
        tags: ['Client - Paiements'],
        parameters: [
            new OA\PathParameter(name: 'code', description: 'Code paiement VERGA', schema: new OA\Schema(type: 'string', example: 'PAY-ABCDEFGH')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statut actuel', content: new OA\JsonContent(ref: '#/components/schemas/PaymentStatusCheckResponse')),
            new OA\Response(response: 404, description: 'Paiement introuvable'),
        ]
    )]
    public function checkPaymentStatus(): void {}

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
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'réservée', 'confirmée', 'annulée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des commandes du client',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ClientCommandeResource')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
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
            new OA\Response(
                response: 200,
                description: 'Détail avec agence, offre, dernier paiement, colis',
                content: new OA\JsonContent(ref: '#/components/schemas/ClientCommandeResource')
            ),
            new OA\Response(response: 404, description: 'Commande introuvable'),
        ]
    )]
    public function showCommande(): void {}

    #[OA\Post(
        path: '/client/commandes/{commande}/paiements',
        operationId: 'clientPayCommandeBalance',
        summary: 'Payer le solde d\'une commande réservée',
        description: 'Initie un nouveau paiement Bamboo pour une commande au statut `réservée`.

- `quantite` : quantité restante à payer (ex. 20 kg si 30/50 déjà payés)
- Commission client recalculée sur ce versement uniquement
- Réponse identique au checkout (`CheckoutResponse`)
- Quand le solde est validé : commande passe à `confirmée`

**Prérequis** : client authentifié, propriétaire de la commande, aucun paiement `en_attente` en cours.',
        tags: ['Client - Commandes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'commande', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SoldePaiementRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Paiement de solde initié', content: new OA\JsonContent(ref: '#/components/schemas/CheckoutResponse')),
            new OA\Response(response: 422, description: 'Commande non réservée ou quantité invalide'),
            new OA\Response(response: 404, description: 'Commande introuvable'),
        ]
    )]
    public function payCommandeBalance(): void {}

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
