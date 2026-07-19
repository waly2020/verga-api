<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class AgenceEndpoints
{
    #[OA\Get(
        path: '/agence/types-agences',
        operationId: 'agenceListTypesAgences',
        summary: 'Lister les types d\'agence',
        description: 'Retourne la liste complète des types d\'agence disponibles (sans pagination). Utilisé notamment pour le formulaire d\'inscription (`type_agence_id`). Aucune authentification requise.',
        tags: ['Agence - Référentiels'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des types d\'agence',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TypeAgenceResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function listTypesAgences(): void {}

    #[OA\Get(
        path: '/agence/types-offres',
        operationId: 'agenceListTypesOffres',
        summary: 'Lister les types d\'offre disponibles',
        description: 'Retourne les types d\'offre plateforme actifs. Si l\'agence est authentifiée, inclut aussi ses types personnalisés (actifs ou non). Sans pagination.',
        tags: ['Agence - Types d\'offre'],
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des types d\'offre',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TypeOffreResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function listTypesOffres(): void {}

    #[OA\Post(
        path: '/agence/types-offres',
        operationId: 'agenceCreateTypeOffre',
        summary: 'Créer un type d\'offre personnalisé',
        description: 'Crée un type d\'offre propre à l\'agence connectée. Les types plateforme (`agence_id` null) ne peuvent pas être créés via cette route.',
        tags: ['Agence - Types d\'offre'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['slug', 'nom', 'unite', 'unite_label', 'quantite_min'],
                properties: [
                    new OA\Property(property: 'slug', type: 'string', example: 'palette'),
                    new OA\Property(property: 'nom', type: 'string', example: 'Palette standard'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'unite', type: 'string', example: 'palette'),
                    new OA\Property(property: 'unite_label', type: 'string', example: 'par palette'),
                    new OA\Property(property: 'quantite_entier', type: 'boolean', example: true),
                    new OA\Property(property: 'quantite_min', type: 'number', format: 'float', example: 1),
                    new OA\Property(property: 'actif', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Type d\'offre créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TypeOffreResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function createTypeOffre(): void {}

    #[OA\Get(
        path: '/agence/types-offres/{typeOffre}',
        operationId: 'agenceShowTypeOffre',
        summary: 'Détail d\'un type d\'offre',
        description: 'Consulte un type plateforme ou un type personnalisé appartenant à l\'agence connectée.',
        tags: ['Agence - Types d\'offre'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'typeOffre', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail du type d\'offre',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TypeOffreResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Type d\'une autre agence'),
            new OA\Response(response: 404, description: 'Type introuvable'),
        ]
    )]
    public function showTypeOffre(): void {}

    #[OA\Patch(
        path: '/agence/types-offres/{typeOffre}',
        operationId: 'agenceUpdateTypeOffre',
        summary: 'Modifier un type d\'offre personnalisé',
        description: 'Met à jour un type créé par l\'agence. Les types plateforme ne sont pas modifiables.',
        tags: ['Agence - Types d\'offre'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'typeOffre', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'unite', type: 'string'),
                    new OA\Property(property: 'unite_label', type: 'string'),
                    new OA\Property(property: 'quantite_entier', type: 'boolean'),
                    new OA\Property(property: 'quantite_min', type: 'number', format: 'float'),
                    new OA\Property(property: 'actif', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Type d\'offre mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/TypeOffreResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Type plateforme ou autre agence'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function updateTypeOffre(): void {}

    #[OA\Delete(
        path: '/agence/types-offres/{typeOffre}',
        operationId: 'agenceDeleteTypeOffre',
        summary: 'Supprimer un type d\'offre personnalisé',
        description: 'Supprime un type créé par l\'agence s\'il n\'est lié à aucune offre.',
        tags: ['Agence - Types d\'offre'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'typeOffre', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Type supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Type d\'offre supprimé avec succès.'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Type plateforme ou autre agence'),
            new OA\Response(response: 422, description: 'Type utilisé par des offres'),
        ]
    )]
    public function deleteTypeOffre(): void {}

    #[OA\Post(
        path: '/agence/register',
        operationId: 'agenceRegister',
        summary: 'Inscription agence',
        description: 'Crée une agence et son compte propriétaire (`AgenceUser` avec rôle `admin-agence`). Retourne un token Bearer pour connexion immédiate.

Peut être envoyé en `multipart/form-data` pour joindre un **logo** et des **documents** (pièce d\'identité, registre de commerce, etc.).
- `logo` : image (max 5 Mo)
- `documents[i][fichier]` : fichier image/PDF (max 10 Mo)
- `documents[i][type_document]` : libellé libre (ex. `piece_identite`, `registre_commerce`)',
        tags: ['Agence - Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['nom', 'email', 'telephone', 'gerant_name', 'gerant_email', 'password', 'password_confirmation'],
                        properties: [
                            new OA\Property(property: 'nom', type: 'string', example: 'Transit Express Libreville'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'contact@transit-express.test'),
                            new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
                            new OA\Property(property: 'type_agence_id', type: 'string', format: 'uuid', nullable: true),
                            new OA\Property(property: 'ville', type: 'string', example: 'Libreville', nullable: true),
                            new OA\Property(property: 'adresse', type: 'string', nullable: true),
                            new OA\Property(property: 'pays', type: 'string', example: 'Gabon', nullable: true),
                            new OA\Property(property: 'gerant_name', type: 'string', example: 'Jean Mbaye'),
                            new OA\Property(property: 'gerant_email', type: 'string', format: 'email', example: 'gerant@transit-express.test'),
                            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                            new OA\Property(property: 'device_name', type: 'string', example: 'angular-backoffice'),
                            new OA\Property(property: 'logo', type: 'string', format: 'binary', description: 'Logo de l\'agence (image)'),
                            new OA\Property(
                                property: 'documents',
                                type: 'array',
                                items: new OA\Items(properties: [
                                    new OA\Property(property: 'fichier', type: 'string', format: 'binary'),
                                    new OA\Property(property: 'type_document', type: 'string', example: 'piece_identite'),
                                ], type: 'object')
                            ),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['nom', 'email', 'telephone', 'gerant_name', 'gerant_email', 'password', 'password_confirmation'],
                    properties: [
                        new OA\Property(property: 'nom', type: 'string', example: 'Transit Express Libreville'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'contact@transit-express.test'),
                        new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
                        new OA\Property(property: 'type_agence_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'ville', type: 'string', example: 'Libreville', nullable: true),
                        new OA\Property(property: 'adresse', type: 'string', nullable: true),
                        new OA\Property(property: 'pays', type: 'string', example: 'Gabon', nullable: true),
                        new OA\Property(property: 'gerant_name', type: 'string', example: 'Jean Mbaye'),
                        new OA\Property(property: 'gerant_email', type: 'string', format: 'email', example: 'gerant@transit-express.test'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                        new OA\Property(property: 'device_name', type: 'string', example: 'angular-backoffice'),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé + token', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/TokenResponse'),
                new OA\Schema(properties: [
                    new OA\Property(property: 'user', properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'role', ref: '#/components/schemas/AgenceRoleResource'),
                        new OA\Property(property: 'agence', type: 'object'),
                    ], type: 'object'),
                ]),
            ])),
            new OA\Response(response: 422, description: 'Validation échouée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Trop de tentatives'),
        ]
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/agence/login',
        operationId: 'agenceLogin',
        summary: 'Connexion agence',
        description: 'Authentifie un utilisateur agence (`agence_users`) et retourne un token Bearer.',
        tags: ['Agence - Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'gerant@agence.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'angular-backoffice'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Connexion réussie', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/TokenResponse'),
                new OA\Schema(properties: [
                    new OA\Property(property: 'user', properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'role', ref: '#/components/schemas/AgenceRoleResource'),
                        new OA\Property(property: 'agence', type: 'object'),
                    ], type: 'object'),
                ]),
            ])),
            new OA\Response(response: 403, description: 'Agence bloquée ou suspendue', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Identifiants invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Trop de tentatives'),
        ]
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/agence/logout',
        operationId: 'agenceLogout',
        summary: 'Déconnexion agence',
        tags: ['Agence - Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token révoqué', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function logout(): void {}

    #[OA\Get(
        path: '/agence/me',
        operationId: 'agenceMe',
        summary: 'Profil agence connectée',
        description: 'Retourne l\'utilisateur et le profil `agence`, y compris le **logo** et les **documents** (`logo`, `documents[]` avec `url`).',
        tags: ['Agence - Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil utilisateur et agence',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/AgenceUserResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Compte non agence ou agence inactive'),
        ]
    )]
    public function me(): void {}

    #[OA\Put(
        path: '/agence/password',
        operationId: 'agenceUpdatePassword',
        summary: 'Modifier le mot de passe',
        tags: ['Agence - Auth'],
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
            new OA\Response(response: 422, description: 'Validation échouée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updatePassword(): void {}

    #[OA\Get(
        path: '/agence/offres',
        operationId: 'agenceListOffres',
        summary: 'Lister les offres de l\'agence',
        tags: ['Agence - Offres'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Recherche par titre', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'archivée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée des offres'),
        ]
    )]
    public function listOffres(): void {}

    #[OA\Post(
        path: '/agence/offres',
        operationId: 'agenceCreateOffre',
        summary: 'Créer une offre',
        tags: ['Agence - Offres'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['titre', 'prix', 'origine', 'destination'],
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'Forfait Particulier Chine → Libreville'),
                    new OA\Property(property: 'type_offre_id', type: 'string', format: 'uuid', description: 'Type d\'offre (recommandé)'),
                    new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'metre_cube', 'conteneur'], description: 'Legacy — requis si type_offre_id absent'),
                    new OA\Property(property: 'prix', type: 'number', format: 'float', example: 8750, description: 'Prix unitaire (FCFA/kg, FCFA/m³ ou FCFA/conteneur)'),
                    new OA\Property(property: 'capacite_illimitee', type: 'boolean', example: false, description: 'Si true, pas de plafond de stock (capacite_totale ignorée)'),
                    new OA\Property(property: 'capacite_totale', type: 'number', format: 'float', nullable: true, example: 30000, description: 'Stock total — requis sauf si capacite_illimitee=true'),
                    new OA\Property(property: 'origine', type: 'string', example: 'Chine'),
                    new OA\Property(property: 'destination', type: 'string', example: 'Libreville'),
                    new OA\Property(property: 'date_depart', type: 'string', format: 'date', nullable: true, example: '2026-07-20', description: 'Date de départ (optionnelle)'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'statut', type: 'string', enum: ['active', 'inactive'], default: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Offre créée'),
            new OA\Response(response: 422, description: 'Validation échouée'),
        ]
    )]
    public function createOffre(): void {}

    #[OA\Get(
        path: '/agence/offres/{offre}',
        operationId: 'agenceShowOffre',
        summary: 'Détail d\'une offre',
        tags: ['Agence - Offres'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'offre', description: 'UUID de l\'offre', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail offre'),
            new OA\Response(response: 404, description: 'Offre introuvable ou autre agence'),
        ]
    )]
    public function showOffre(): void {}

    #[OA\Patch(
        path: '/agence/offres/{offre}',
        operationId: 'agenceUpdateOffre',
        summary: 'Modifier une offre',
        tags: ['Agence - Offres'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'offre', description: 'UUID de l\'offre', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['titre', 'prix', 'origine', 'destination', 'statut'],
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'Forfait Particulier Chine → Libreville'),
                    new OA\Property(property: 'type_offre_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'metre_cube', 'conteneur']),
                    new OA\Property(property: 'prix', type: 'number', format: 'float', example: 8750),
                    new OA\Property(property: 'capacite_illimitee', type: 'boolean', example: false, description: 'Si true, pas de plafond de stock'),
                    new OA\Property(property: 'capacite_totale', type: 'number', format: 'float', nullable: true, example: 30000, description: 'Requis sauf si capacite_illimitee=true ; doit rester ≥ quantité déjà réservée'),
                    new OA\Property(property: 'origine', type: 'string', example: 'Chine'),
                    new OA\Property(property: 'destination', type: 'string', example: 'Libreville'),
                    new OA\Property(property: 'date_depart', type: 'string', format: 'date', nullable: true, example: '2026-07-20', description: 'Date de départ (optionnelle)'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'statut', type: 'string', enum: ['active', 'inactive', 'archivée']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Offre mise à jour'),
            new OA\Response(response: 404, description: 'Offre introuvable ou autre agence'),
            new OA\Response(response: 422, description: 'Validation échouée'),
        ]
    )]
    public function updateOffre(): void {}

    #[OA\Delete(
        path: '/agence/offres/{offre}',
        operationId: 'agenceDeleteOffre',
        summary: 'Supprimer une offre',
        description: 'Refusé si l\'offre est liée à au moins une commande.',
        tags: ['Agence - Offres'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'offre', description: 'UUID de l\'offre', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Offre supprimée', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Offre introuvable ou autre agence'),
            new OA\Response(response: 422, description: 'Suppression impossible (commandes liées)', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function deleteOffre(): void {}

    #[OA\Get(
        path: '/agence/commandes',
        operationId: 'agenceListCommandes',
        summary: 'Lister les commandes de l\'agence',
        tags: ['Agence - Commandes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Recherche par code commande', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'réservée', 'confirmée', 'annulée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des commandes (quantités avec `*_label` et `offre.type_offre`)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AgenceCommandeResource')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function listCommandes(): void {}

    #[OA\Get(
        path: '/agence/commandes/{commande}',
        operationId: 'agenceShowCommande',
        summary: 'Détail d\'une commande',
        tags: ['Agence - Commandes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'commande', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail avec client, offre, paiement, colis (quantités formatées)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgenceCommandeResource'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Commande introuvable'),
        ]
    )]
    public function showCommande(): void {}

    #[OA\Patch(
        path: '/agence/commandes/{commande}/statut',
        operationId: 'agenceUpdateCommandeStatut',
        summary: 'Changer le statut d\'une commande',
        description: 'Transitions : `en_attente` → `confirmée` ou `annulée`.',
        tags: ['Agence - Commandes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'commande', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['statut'],
                properties: [
                    new OA\Property(property: 'statut', type: 'string', enum: ['confirmée', 'annulée']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgenceCommandeResource'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Transition non autorisée'),
        ]
    )]
    public function updateCommandeStatut(): void {}

    #[OA\Get(
        path: '/agence/colis',
        operationId: 'agenceListColis',
        summary: 'Lister les colis de l\'agence',
        tags: ['Agence - Colis'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Recherche par référence', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['chez_client', 'déposé', 'en_transit', 'arrivé', 'récupéré'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des colis (`quantite_label`, `poids_label`)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AgenceColisResource')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function listColis(): void {}

    #[OA\Get(
        path: '/agence/colis/{colis}',
        operationId: 'agenceShowColis',
        summary: 'Détail d\'un colis',
        tags: ['Agence - Colis'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'colis', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail avec commande, historique et `next_statut`',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgenceColisDetailResource'),
                        new OA\Property(property: 'next_statut', type: 'string', nullable: true, enum: ['déposé', 'en_transit', 'arrivé', 'récupéré']),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function showColis(): void {}

    #[OA\Patch(
        path: '/agence/colis/{colis}/statut',
        operationId: 'agenceUpdateColisStatut',
        summary: 'Faire avancer le statut d\'un colis (suivi logistique)',
        description: 'Fait passer le colis à l\'étape suivante du flux logistique et enregistre une entrée dans l\'historique.

**Flux autorisé** : `chez_client` → `déposé` → `en_transit` → `arrivé` → `récupéré`

- Sans body : avance automatiquement au statut suivant
- Avec `statut` : doit correspondre exactement au prochain statut attendu (utile pour le front)
- `commentaire` optionnel : note visible dans l\'historique
- `date_statut` optionnel : date saisie pour cette étape (distincte de l\'horodatage d\'enregistrement)

Réponse : détail du colis mis à jour + `next_statut` (prochaine étape ou `null` si terminé).',
        tags: ['Agence - Colis'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'colis', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'statut', type: 'string', enum: ['déposé', 'en_transit', 'arrivé', 'récupéré'], nullable: true, description: 'Prochain statut attendu (optionnel si avance automatique)'),
                    new OA\Property(property: 'date_statut', type: 'string', format: 'date', nullable: true, example: '2026-07-20', description: 'Date saisie pour cette étape (optionnelle)'),
                    new OA\Property(property: 'commentaire', type: 'string', maxLength: 500, nullable: true, example: 'Colis embarqué à Libreville'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgenceColisDetailResource'),
                        new OA\Property(property: 'next_statut', type: 'string', nullable: true, enum: ['déposé', 'en_transit', 'arrivé', 'récupéré'], example: 'déposé'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Colis introuvable ou autre agence'),
            new OA\Response(response: 422, description: 'Statut final ou transition invalide', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updateColisStatut(): void {}

    #[OA\Get(
        path: '/agence/reclamations',
        operationId: 'agenceListReclamations',
        summary: 'Lister les réclamations',
        tags: ['Agence - Réclamations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', schema: new OA\Schema(type: 'string')),
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
        path: '/agence/reclamations',
        operationId: 'agenceCreateReclamation',
        summary: 'Créer une réclamation (agence)',
        tags: ['Agence - Réclamations'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nom', 'prenom', 'telephone', 'email', 'objet', 'description'],
                properties: [
                    new OA\Property(property: 'commande_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'nom', type: 'string'),
                    new OA\Property(property: 'prenom', type: 'string'),
                    new OA\Property(property: 'telephone', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'objet', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Réclamation créée'),
        ]
    )]
    public function createReclamation(): void {}

    #[OA\Get(
        path: '/agence/reclamations/{reclamation}',
        operationId: 'agenceShowReclamation',
        summary: 'Détail d\'une réclamation',
        tags: ['Agence - Réclamations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'reclamation', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail réclamation'),
        ]
    )]
    public function showReclamation(): void {}

    #[OA\Patch(
        path: '/agence/reclamations/{reclamation}/statut',
        operationId: 'agenceUpdateReclamationStatut',
        summary: 'Traiter une réclamation',
        description: 'Transitions : ouverte → en_cours|fermée ; en_cours → résolue|fermée.',
        tags: ['Agence - Réclamations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'reclamation', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['statut'],
                properties: [
                    new OA\Property(property: 'statut', type: 'string', enum: ['en_cours', 'résolue', 'fermée']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Statut mis à jour'),
            new OA\Response(response: 422, description: 'Transition non autorisée'),
        ]
    )]
    public function updateReclamationStatut(): void {}

    #[OA\Get(
        path: '/agence/paiements',
        operationId: 'agenceListPaiements',
        summary: 'Lister les paiements de l\'agence',
        tags: ['Agence - Paiements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', description: 'Recherche par référence', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'validé', 'remboursé', 'échec'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des paiements (`quantite_label`)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AgencePaiementResource')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function listPaiements(): void {}

    #[OA\Get(
        path: '/agence/reversements',
        operationId: 'agenceListReversements',
        summary: 'Lister les reversements de l\'agence',
        description: 'Historique des reversements enregistrés pour l\'agence connectée (lecture seule).',
        tags: ['Agence - Finance'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'effectué'])),
            new OA\QueryParameter(name: 'periode', description: 'Filtrer par période (AAAA-MM)', schema: new OA\Schema(type: 'string', example: '2026-07')),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des reversements',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AgenceReversementResource')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès réservé aux agences'),
        ]
    )]
    public function listReversements(): void {}

    #[OA\Get(
        path: '/agence/roles',
        operationId: 'agenceListRoles',
        summary: 'Lister les rôles assignables',
        description: 'Retourne les rôles actifs non système définis par VERGA. Le rôle propriétaire `admin-agence` n’est pas assignable. Consultation seule — les agences ne peuvent pas créer ni modifier les rôles.',
        tags: ['Agence - Utilisateurs'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Rôles disponibles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AgenceRoleResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès réservé aux agences'),
        ]
    )]
    public function listRoles(): void {}

    #[OA\Get(
        path: '/agence/users',
        operationId: 'agenceListUsers',
        summary: 'Lister les utilisateurs de l\'agence',
        tags: ['Agence - Utilisateurs'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AgenceUserResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Accès réservé aux agences'),
        ]
    )]
    public function listUsers(): void {}

    #[OA\Post(
        path: '/agence/users',
        operationId: 'agenceCreateUser',
        summary: 'Créer un utilisateur agence',
        tags: ['Agence - Utilisateurs'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'agence_role_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'telephone', type: 'string', nullable: true),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                    new OA\Property(property: 'agence_role_id', type: 'string', format: 'uuid', description: 'Rôle actif non système défini par VERGA'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur créé', content: new OA\JsonContent(ref: '#/components/schemas/AgenceUserResource')),
            new OA\Response(response: 422, description: 'Validation échouée'),
        ]
    )]
    public function createUser(): void {}

    #[OA\Patch(
        path: '/agence/users/{agenceUser}',
        operationId: 'agenceUpdateUser',
        summary: 'Modifier un utilisateur agence',
        tags: ['Agence - Utilisateurs'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'agenceUser', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'telephone', type: 'string', nullable: true),
                new OA\Property(property: 'agence_role_id', type: 'string', format: 'uuid', description: 'Rôle actif non système défini par VERGA'),
                new OA\Property(property: 'statut', type: 'string', enum: ['actif', 'suspendu']),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/AgenceUserResource')),
            new OA\Response(response: 404, description: 'Utilisateur introuvable'),
            new OA\Response(response: 422, description: 'Validation échouée'),
        ]
    )]
    public function updateUser(): void {}

    #[OA\Delete(
        path: '/agence/users/{agenceUser}',
        operationId: 'agenceDeleteUser',
        summary: 'Retirer un utilisateur agence',
        tags: ['Agence - Utilisateurs'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'agenceUser', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur retiré', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Utilisateur introuvable'),
            new OA\Response(response: 422, description: 'Impossible de retirer le propriétaire'),
        ]
    )]
    public function deleteUser(): void {}
}
