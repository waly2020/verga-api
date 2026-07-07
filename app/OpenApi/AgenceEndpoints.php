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
        summary: 'Lister les types d\'offre',
        description: 'Retourne la liste complète des types d\'offre actifs (sans pagination). Utilisé pour les formulaires de création d\'offre (`type_offre_id`). Aucune authentification requise.',
        tags: ['Agence - Référentiels'],
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
        path: '/agence/register',
        operationId: 'agenceRegister',
        summary: 'Inscription agence',
        description: 'Crée un compte gérant (`role=agence`) et le profil agence associé. Retourne un token Bearer pour connexion immédiate.',
        tags: ['Agence - Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
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
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé + token', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/TokenResponse'),
                new OA\Schema(properties: [
                    new OA\Property(property: 'user', properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'role', type: 'string', example: 'agence'),
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
        description: 'Authentifie le gérant de l\'agence (email du compte `users`) et retourne un token Bearer.',
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
                        new OA\Property(property: 'role', type: 'string', example: 'agence'),
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
        tags: ['Agence - Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profil utilisateur et agence'),
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
                required: ['titre', 'prix', 'capacite_totale', 'origine', 'destination'],
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'Forfait Particulier Chine → Libreville'),
                    new OA\Property(property: 'type_offre_id', type: 'string', format: 'uuid', description: 'Type d\'offre (recommandé)'),
                    new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'metre_cube', 'conteneur'], description: 'Legacy — requis si type_offre_id absent'),
                    new OA\Property(property: 'prix', type: 'number', format: 'float', example: 8750, description: 'Prix unitaire (FCFA/kg, FCFA/m³ ou FCFA/conteneur)'),
                    new OA\Property(property: 'capacite_totale', type: 'number', format: 'float', example: 30000, description: 'Stock total (kg, m³ ou nombre de conteneurs)'),
                    new OA\Property(property: 'origine', type: 'string', example: 'Chine'),
                    new OA\Property(property: 'destination', type: 'string', example: 'Libreville'),
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
                required: ['titre', 'prix', 'capacite_totale', 'origine', 'destination', 'statut'],
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'Forfait Particulier Chine → Libreville'),
                    new OA\Property(property: 'type_offre_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'metre_cube', 'conteneur']),
                    new OA\Property(property: 'prix', type: 'number', format: 'float', example: 8750),
                    new OA\Property(property: 'capacite_totale', type: 'number', format: 'float', example: 30000, description: 'Doit rester ≥ quantité déjà réservée sur l\'offre'),
                    new OA\Property(property: 'origine', type: 'string', example: 'Chine'),
                    new OA\Property(property: 'destination', type: 'string', example: 'Libreville'),
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
            new OA\Response(response: 200, description: 'Liste paginée'),
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
            new OA\Response(response: 200, description: 'Détail avec client, offre, paiement, colis'),
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
            new OA\Response(response: 200, description: 'Statut mis à jour'),
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
            new OA\Response(response: 200, description: 'Liste paginée'),
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
            new OA\Response(response: 200, description: 'Détail avec historique et next_statut'),
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
            new OA\Response(response: 200, description: 'Liste paginée'),
        ]
    )]
    public function listPaiements(): void {}
}
