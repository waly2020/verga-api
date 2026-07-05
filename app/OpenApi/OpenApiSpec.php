<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.1.0',
    title: 'VERGA API',
    description: 'API REST pour les applications externes VERGA (back-office agence Angular, application client mobile/web). Authentification Bearer Sanctum.

**Réservations et paiements multiples**
- `quantite_reservee` : quantité bloquée sur l\'offre (ex. 50 kg)
- `quantite` : quantité payée lors du versement en cours (ex. 30 kg)
- Après validation du 1er paiement partiel : statut commande `réservée`, stock bloqué sur la quantité réservée
- Solde via `POST /client/commandes/{commande}/paiements` (auth requise)
- Commission client recalculée à chaque versement sur la quantité payée',
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
#[OA\Tag(name: 'Agence - Référentiels', description: 'Données de référence pour l\'inscription et les formulaires agence')]
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
    schema: 'ColisPhotoResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'chemin', type: 'string', example: 'colis/uuid/photo.jpg'),
        new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'http://localhost/storage/colis/uuid/photo.jpg'),
        new OA\Property(property: 'ordre', type: 'integer', example: 0),
    ]
)]
#[OA\Schema(
    schema: 'HistoriqueColisResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'statut', type: 'string', enum: ['déposé', 'en_transit', 'arrivé', 'récupéré']),
        new OA\Property(property: 'commentaire', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'user', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'AgenceColisDetailResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'reference', type: 'string', example: 'COL-ABCDEFGH'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Vêtements et accessoires'),
        new OA\Property(property: 'poids', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'volume', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'statut', type: 'string', enum: ['déposé', 'en_transit', 'arrivé', 'récupéré']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'commande', type: 'object', nullable: true),
        new OA\Property(
            property: 'photos',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ColisPhotoResource')
        ),
        new OA\Property(
            property: 'historique',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/HistoriqueColisResource')
        ),
    ]
)]
#[OA\Schema(
    schema: 'ClientColisResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'reference', type: 'string', example: 'COL-ABCDEFGH'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'poids', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'volume', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'statut', type: 'string', enum: ['déposé', 'en_transit', 'arrivé', 'récupéré']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'commande', type: 'object', nullable: true),
        new OA\Property(property: 'agence', type: 'object', nullable: true),
        new OA\Property(
            property: 'photos',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ColisPhotoResource')
        ),
    ]
)]
#[OA\Schema(
    schema: 'TypeOffreResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'slug', type: 'string', enum: ['particulier', 'metre_cube', 'conteneur'], example: 'particulier'),
        new OA\Property(property: 'nom', type: 'string', example: 'Particulier (au kg)'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'unite', type: 'string', example: 'kg'),
        new OA\Property(property: 'unite_label', type: 'string', example: 'au kg'),
        new OA\Property(property: 'quantite_entier', type: 'boolean', example: false),
        new OA\Property(property: 'quantite_min', type: 'number', format: 'float', example: 0.001),
    ]
)]
#[OA\Schema(
    schema: 'TypeAgenceResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'nom', type: 'string', example: 'Transitaire'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Agence de transit et groupage'),
    ]
)]
#[OA\Schema(
    schema: 'ClientOffreResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'titre', type: 'string', example: 'Groupage Paris'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['particulier', 'metre_cube', 'conteneur'], description: 'Legacy — conservé pour compatibilité'),
        new OA\Property(property: 'type_offre_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'type_offre', ref: '#/components/schemas/TypeOffreResource', nullable: true),
        new OA\Property(property: 'prix', type: 'number', format: 'float', example: 2500),
        new OA\Property(property: 'capacite_totale', type: 'number', format: 'float', example: 1000),
        new OA\Property(property: 'capacite_disponible', type: 'number', format: 'float', example: 750),
        new OA\Property(property: 'origine', type: 'string', example: 'Libreville'),
        new OA\Property(property: 'destination', type: 'string', example: 'Paris'),
        new OA\Property(property: 'statut', type: 'string', enum: ['active', 'inactive', 'archivée'], example: 'active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'agence', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'nom', type: 'string'),
            new OA\Property(property: 'ville', type: 'string', nullable: true),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'ClientCommissionConfig',
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['pourcentage', 'fixe'], example: 'pourcentage'),
        new OA\Property(property: 'valeur', type: 'number', format: 'float', example: 5, description: 'Pourcentage ou montant fixe FCFA'),
        new OA\Property(property: 'libelle', type: 'string', nullable: true, example: 'Frais de service'),
    ]
)]
#[OA\Schema(
    schema: 'OffrePricingEstimateResponse',
    properties: [
        new OA\Property(property: 'offre_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'quantite', type: 'number', format: 'float', example: 10),
        new OA\Property(property: 'prix_unitaire', type: 'number', format: 'float', example: 2500, description: 'Prix unitaire de l\'offre (FCFA)'),
        new OA\Property(property: 'montant_sous_total', type: 'number', format: 'float', example: 25000),
        new OA\Property(property: 'montant_commission_client', type: 'number', format: 'float', example: 1250),
        new OA\Property(property: 'montant_total', type: 'number', format: 'float', example: 26250, description: 'Montant total que le client paiera'),
        new OA\Property(property: 'capacite_disponible', type: 'number', format: 'float', example: 1000),
        new OA\Property(property: 'stock_suffisant', type: 'boolean', example: true),
        new OA\Property(property: 'commission', ref: '#/components/schemas/ClientCommissionConfig', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'CheckoutResponse',
    properties: [
        new OA\Property(property: 'commande_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'code', type: 'string', example: 'CMD-ABCDEFGH'),
        new OA\Property(property: 'commande_statut', type: 'string', enum: ['en_attente', 'réservée', 'confirmée', 'annulée']),
        new OA\Property(property: 'quantite_reservee', type: 'number', format: 'float', example: 50),
        new OA\Property(property: 'quantite_payee', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'quantite_a_payer', type: 'number', format: 'float', example: 30),
        new OA\Property(property: 'quantite_restante', type: 'number', format: 'float', example: 20),
        new OA\Property(property: 'montant_sous_total', type: 'number', format: 'float', example: 25000, description: 'Prix offre × quantité du paiement en cours'),
        new OA\Property(property: 'montant_commission_client', type: 'number', format: 'float', example: 1250, description: 'Commission VERGA sur ce paiement'),
        new OA\Property(property: 'montant_total', type: 'number', format: 'float', example: 26250, description: 'Montant envoyé à Bamboo Pay pour ce paiement'),
        new OA\Property(property: 'paiement_code', type: 'string', example: 'PAY-ABCDEFGH'),
        new OA\Property(property: 'redirect_url', type: 'string', format: 'uri', example: 'https://devfront-bamboopay.ventis.group/pay/abc'),
        new OA\Property(property: 'verification_url', type: 'string', format: 'uri', example: 'http://localhost/api/v1/client/paiements/PAY-ABCDEFGH/statut'),
        new OA\Property(property: 'mode', type: 'string', enum: ['reservation', 'complet'], description: 'reservation = paiement partiel, complet = solde total'),
    ]
)]
#[OA\Schema(
    schema: 'SoldePaiementRequest',
    required: ['quantite'],
    properties: [
        new OA\Property(property: 'quantite', type: 'number', format: 'float', example: 20, description: 'Quantité à payer (≤ quantité restante de la commande)'),
    ]
)]
#[OA\Schema(
    schema: 'ClientCommandeResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'code', type: 'string', example: 'CMD-ABCDEFGH'),
        new OA\Property(property: 'quantite', type: 'number', format: 'float', description: 'Quantité réservée totale', example: 50),
        new OA\Property(property: 'quantite_payee', type: 'number', format: 'float', example: 30),
        new OA\Property(property: 'quantite_restante', type: 'number', format: 'float', example: 20),
        new OA\Property(property: 'montant_sous_total', type: 'number', format: 'float', description: 'Cumul des sous-totaux des paiements validés'),
        new OA\Property(property: 'montant_commission_client', type: 'number', format: 'float', description: 'Cumul des commissions client des paiements validés'),
        new OA\Property(property: 'montant_total', type: 'number', format: 'float', description: 'Cumul des montants payés validés'),
        new OA\Property(property: 'statut', type: 'string', enum: ['en_attente', 'réservée', 'confirmée', 'annulée']),
        new OA\Property(property: 'nom', type: 'string', nullable: true),
        new OA\Property(property: 'prenom', type: 'string', nullable: true),
        new OA\Property(property: 'telephone', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'agence', type: 'object', nullable: true),
        new OA\Property(property: 'offre', type: 'object', nullable: true),
        new OA\Property(property: 'paiement', ref: '#/components/schemas/ClientPaiementResource', nullable: true, description: 'Dernier paiement initié'),
        new OA\Property(property: 'colis', type: 'array', items: new OA\Items(type: 'object')),
    ]
)]
#[OA\Schema(
    schema: 'ClientPaiementResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'code', type: 'string', example: 'PAY-ABCDEFGH'),
        new OA\Property(property: 'quantite', type: 'number', format: 'float', example: 2.5),
        new OA\Property(property: 'montant_sous_total', type: 'number', format: 'float', example: 75000, description: 'Prix offre × quantité de ce paiement'),
        new OA\Property(property: 'montant_commission_client', type: 'number', format: 'float', example: 3750, description: 'Commission VERGA sur ce paiement'),
        new OA\Property(property: 'montant', type: 'number', format: 'float', example: 78750, description: 'Total débité au client pour ce paiement'),
        new OA\Property(property: 'methode', type: 'string', example: 'bamboo_redirect'),
        new OA\Property(property: 'reference', type: 'string', nullable: true),
        new OA\Property(property: 'bamboo_reference', type: 'string', nullable: true),
        new OA\Property(property: 'statut', type: 'string', enum: ['en_attente', 'validé', 'remboursé', 'échec']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'commande', type: 'object', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PaymentStatusCheckResponse',
    properties: [
        new OA\Property(property: 'paiement_code', type: 'string', example: 'PAY-ABCDEFGH'),
        new OA\Property(property: 'statut', type: 'string', enum: ['en_attente', 'validé', 'échec', 'remboursé']),
        new OA\Property(property: 'bamboo_reference', type: 'string', nullable: true, example: 'TXN-2025-000381'),
        new OA\Property(property: 'quantite', type: 'number', format: 'float', example: 2.5, description: 'Quantité couverte par ce paiement'),
        new OA\Property(property: 'montant_sous_total', type: 'number', format: 'float', example: 75000),
        new OA\Property(property: 'montant_commission_client', type: 'number', format: 'float', example: 3750),
        new OA\Property(property: 'montant_total', type: 'number', format: 'float', example: 78750, description: 'Montant total de ce paiement'),
        new OA\Property(property: 'commande_code', type: 'string', example: 'CMD-ABCDEFGH'),
        new OA\Property(property: 'commande_statut', type: 'string', enum: ['en_attente', 'réservée', 'confirmée', 'annulée']),
        new OA\Property(property: 'quantite_reservee', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'quantite_payee', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'quantite_restante', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'commande_montant_sous_total', type: 'number', format: 'float', nullable: true, description: 'Cumul sous-total transport des paiements validés'),
        new OA\Property(property: 'commande_montant_commission_client', type: 'number', format: 'float', nullable: true, description: 'Cumul commission VERGA des paiements validés'),
        new OA\Property(property: 'commande_montant_total', type: 'number', format: 'float', nullable: true, description: 'Cumul total payé sur la commande'),
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
                new OA\Property(property: 'total_depense', type: 'number', format: 'float', description: 'Total payé (transport + commission client)'),
                new OA\Property(property: 'total_sous_total', type: 'number', format: 'float', description: 'Cumul sous-total transport'),
                new OA\Property(property: 'total_commissions', type: 'number', format: 'float', description: 'Cumul commissions VERGA côté client'),
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
                new OA\Property(property: 'total_paiements', type: 'number', format: 'float', description: 'Total payé par les clients'),
                new OA\Property(property: 'total_sous_total', type: 'number', format: 'float', description: 'Cumul sous-total transport'),
                new OA\Property(property: 'total_commissions_client', type: 'number', format: 'float', description: 'Commissions VERGA côté client'),
                new OA\Property(property: 'total_commissions_agence', type: 'number', format: 'float', description: 'Commissions VERGA côté agence'),
                new OA\Property(property: 'total_commissions', type: 'number', format: 'float', description: 'Alias de total_commissions_agence'),
                new OA\Property(property: 'revenu_net_estime', type: 'number', format: 'float', description: 'Sous-total transport − commission agence'),
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
