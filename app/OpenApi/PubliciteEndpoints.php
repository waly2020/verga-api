<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class PubliciteEndpoints
{
    #[OA\Get(
        path: '/publicites',
        operationId: 'listPublicitesVisible',
        summary: 'Lister les publicités publiées',
        description: 'Catalogue public : statut `publiée` et dates encore valides. Aucune authentification. Les publicités expirées sont retirées automatiquement à la requête.',
        tags: ['Publicités'],
        parameters: [
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des publicités visibles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PubliciteResource')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
        ]
    )]
    public function catalog(): void {}

    #[OA\Get(
        path: '/publicites/paiements/{code}/statut',
        operationId: 'publicitePaymentStatus',
        summary: 'Vérifier le statut d\'un paiement publicité',
        description: 'Endpoint public (sans authentification). Si le paiement est encore `en_attente`, une vérification Bamboo est tentée avant de répondre.',
        tags: ['Publicités'],
        parameters: [
            new OA\PathParameter(name: 'code', description: 'Code paiement VERGA (préfixe PUB-)', schema: new OA\Schema(type: 'string', example: 'PUB-ABCDEFGH')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut du paiement et de la publicité associée',
                content: new OA\JsonContent(ref: '#/components/schemas/PublicitePaymentStatusResponse')
            ),
            new OA\Response(response: 404, description: 'Paiement introuvable'),
        ]
    )]
    public function paymentStatus(): void {}

    #[OA\Get(
        path: '/agence/publicites/configuration',
        operationId: 'agencePubliciteConfiguration',
        summary: 'Consulter le tarif publicité',
        description: 'Retourne le prix par jour, le type de frais et un exemple de calcul sur 7 jours. Montants en **entiers FCFA**.',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Configuration tarifaire active',
                content: new OA\JsonContent(ref: '#/components/schemas/PubliciteConfigurationResponse')
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function agenceConfiguration(): void {}

    #[OA\Get(
        path: '/agence/publicites',
        operationId: 'agenceListPublicites',
        summary: 'Lister les publicités de l\'agence',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'validée', 'refusée', 'publiée', 'expirée', 'retirée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des publicités de l\'agence',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PubliciteResource')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function agenceIndex(): void {}

    #[OA\Post(
        path: '/agence/publicites',
        operationId: 'agenceCreatePublicite',
        summary: 'Créer une demande de publicité',
        description: 'Envoie `multipart/form-data`. `offre_id` optionnel — uniquement une offre appartenant à l\'agence. Image obligatoire (max 5 Mo). Statut initial : `en_attente`, paiement : `non_payé`.',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['titre', 'date_debut', 'date_fin', 'image'],
                    properties: [
                        new OA\Property(property: 'titre', type: 'string', example: 'Pub été'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'lien', type: 'string', format: 'uri', nullable: true),
                        new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-08-20'),
                        new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2026-08-26'),
                        new OA\Property(property: 'offre_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Demande créée (en_attente)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Validation échouée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function agenceStore(): void {}

    #[OA\Get(
        path: '/agence/publicites/{publicite}',
        operationId: 'agenceShowPublicite',
        summary: 'Détail d\'une publicité agence',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail de la publicité',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
        ]
    )]
    public function agenceShow(): void {}

    #[OA\Patch(
        path: '/agence/publicites/{publicite}',
        operationId: 'agenceUpdatePublicite',
        summary: 'Modifier une publicité en attente ou refusée',
        description: 'Modifiable uniquement si statut `en_attente` ou `refusée`. Envoie `multipart/form-data` ; l\'image est optionnelle à la mise à jour.',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['titre', 'date_debut', 'date_fin'],
                    properties: [
                        new OA\Property(property: 'titre', type: 'string'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'lien', type: 'string', format: 'uri', nullable: true),
                        new OA\Property(property: 'date_debut', type: 'string', format: 'date'),
                        new OA\Property(property: 'date_fin', type: 'string', format: 'date'),
                        new OA\Property(property: 'offre_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Publicité mise à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
            new OA\Response(response: 422, description: 'Publicité non modifiable ou validation échouée'),
        ]
    )]
    public function agenceUpdate(): void {}

    #[OA\Post(
        path: '/agence/publicites/{publicite}/resoumettre',
        operationId: 'agenceResoumettrePublicite',
        summary: 'Resoumettre une publicité refusée',
        description: 'Passe le statut à `en_attente` et efface le motif de refus. Uniquement si statut actuel : `refusée`.',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Publicité resoumise (en_attente)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
            new OA\Response(response: 422, description: 'Seule une publicité refusée peut être resoumise'),
        ]
    )]
    public function agenceResoumettre(): void {}

    #[OA\Post(
        path: '/agence/publicites/{publicite}/paiement',
        operationId: 'agencePayerPublicite',
        summary: 'Payer une publicité validée',
        description: 'Parcours Bamboo dédié, indépendant des paiements de commandes. Prérequis : statut `validée`, aucun paiement `en_attente` en cours. Callback webhook : `POST /payments/bamboo-pay/publicites/callback`.',
        tags: ['Agence - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Paiement initié — rediriger l\'utilisateur vers `redirect_url`',
                content: new OA\JsonContent(ref: '#/components/schemas/PublicitePaymentInitResponse')
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
            new OA\Response(response: 422, description: 'Publicité non payable ou paiement déjà en cours'),
        ]
    )]
    public function agencePayer(): void {}

    #[OA\Get(
        path: '/client/publicites',
        operationId: 'clientListPublicites',
        summary: 'Lister les publicités du client',
        tags: ['Client - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'statut', schema: new OA\Schema(type: 'string', enum: ['en_attente', 'validée', 'refusée', 'publiée', 'expirée', 'retirée'])),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des publicités du client',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PubliciteResource')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function clientIndex(): void {}

    #[OA\Post(
        path: '/client/publicites',
        operationId: 'clientCreatePublicite',
        summary: 'Créer une demande de publicité client',
        description: 'Envoie `multipart/form-data`. Pas d\'offre rattachable (`offre_id` ignoré). Image obligatoire (max 5 Mo). Statut initial : `en_attente`.',
        tags: ['Client - Publicités'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['titre', 'date_debut', 'date_fin', 'image'],
                    properties: [
                        new OA\Property(property: 'titre', type: 'string', example: 'Produit client'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'lien', type: 'string', format: 'uri', nullable: true),
                        new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-09-01'),
                        new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2026-09-03'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Demande créée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Validation échouée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function clientStore(): void {}

    #[OA\Get(
        path: '/client/publicites/{publicite}',
        operationId: 'clientShowPublicite',
        summary: 'Détail d\'une publicité client',
        tags: ['Client - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail de la publicité',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
        ]
    )]
    public function clientShow(): void {}

    #[OA\Patch(
        path: '/client/publicites/{publicite}',
        operationId: 'clientUpdatePublicite',
        summary: 'Modifier une publicité en attente ou refusée',
        description: 'Modifiable uniquement si statut `en_attente` ou `refusée`. Envoie `multipart/form-data` ; l\'image est optionnelle à la mise à jour.',
        tags: ['Client - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['titre', 'date_debut', 'date_fin'],
                    properties: [
                        new OA\Property(property: 'titre', type: 'string'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'lien', type: 'string', format: 'uri', nullable: true),
                        new OA\Property(property: 'date_debut', type: 'string', format: 'date'),
                        new OA\Property(property: 'date_fin', type: 'string', format: 'date'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Publicité mise à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
            new OA\Response(response: 422, description: 'Publicité non modifiable ou validation échouée'),
        ]
    )]
    public function clientUpdate(): void {}

    #[OA\Post(
        path: '/client/publicites/{publicite}/resoumettre',
        operationId: 'clientResoumettrePublicite',
        summary: 'Resoumettre une publicité refusée',
        description: 'Passe le statut à `en_attente` et efface le motif de refus. Uniquement si statut actuel : `refusée`.',
        tags: ['Client - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Publicité resoumise (en_attente)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PubliciteResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
            new OA\Response(response: 422, description: 'Seule une publicité refusée peut être resoumise'),
        ]
    )]
    public function clientResoumettre(): void {}

    #[OA\Post(
        path: '/client/publicites/{publicite}/paiement',
        operationId: 'clientPayerPublicite',
        summary: 'Payer une publicité client validée',
        description: 'Parcours Bamboo dédié. Prérequis : statut `validée`, aucun paiement `en_attente` en cours. Callback webhook : `POST /payments/bamboo-pay/publicites/callback`.',
        tags: ['Client - Publicités'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'publicite', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Paiement initié — rediriger l\'utilisateur vers `redirect_url`',
                content: new OA\JsonContent(ref: '#/components/schemas/PublicitePaymentInitResponse')
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Publicité introuvable'),
            new OA\Response(response: 422, description: 'Publicité non payable ou paiement déjà en cours'),
        ]
    )]
    public function clientPayer(): void {}

    #[OA\Post(
        path: '/payments/bamboo-pay/publicites/callback',
        operationId: 'publiciteBambooCallback',
        summary: 'Webhook Bamboo dédié aux publicités',
        description: 'Callback serveur Bamboo Pay pour les paiements publicité (`PUB-`). Ne pas confondre avec le callback des commandes.',
        tags: ['Paiement - Bamboo Pay'],
        responses: [
            new OA\Response(response: 200, description: 'Notification reçue'),
        ]
    )]
    public function callback(): void {}
}
