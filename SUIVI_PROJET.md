# Suivi du projet VERGA

> Plateforme de mise en relation entre agences de transit et clients (Gabon / Afrique).
> Document de suivi — à mettre à jour au fil de l'avancement.

---

## Vue d'ensemble

| Élément | Détail |
|---------|--------|
| **Stack backend** | Laravel 13 · Sanctum (API) · SQLite (dev) |
| **Back-office admin VERGA** | Inertia · React — routes **`web.php`** (interne, ce dépôt) |
| **Back-office agence** | Angular — consomme **`api.php`** (application externe) |
| **Approche** | Découper chaque fonctionnalité en petits incréments livrables |
| **Ordre de développement** | 1. Web admin (interne) → 2. API (apps externes) |

---

## Architecture des routes

```
┌─────────────────────────────────────────────────────────────────┐
│  routes/web.php  —  Applications INTERNES (session + Inertia)   │
│  • Back-office admin VERGA (React, ce dépôt)                    │
│  • Auth Fortify, middleware admin, pages /admin/*               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  routes/api.php  —  Applications EXTERNES (JSON + Sanctum)      │
│  • Back-office agence (Angular, autre dépôt)                      │
│  • App mobile client (futur)                                    │
│  • Intégrations tierces (webhooks, etc.)                        │
│  ⚠ Ne pas y mettre de routes pour le back-office admin interne  │
└─────────────────────────────────────────────────────────────────┘
```

| Fichier | Consommateur | Auth | Format |
|---------|--------------|------|--------|
| `routes/web.php` + `routes/admin.php` | Admin VERGA (React) | Session Fortify | Inertia / HTML |
| `routes/api.php` + `routes/api/*` | Agence (Angular), clients, tiers | Bearer Sanctum | JSON |

---

## Phases du projet

```
Phase 1 — Web admin interne (routes/web.php)     [ EN COURS ]
Phase 2 — API apps externes (routes/api.php)     [ EN COURS ]
```

---

## Légende des statuts

| Statut | Signification |
|--------|---------------|
| `[ ]` | À faire |
| `[~]` | En cours |
| `[x]` | Terminé |
| `[-]` | Bloqué / en attente |

---

# Phase 1 — Web admin interne (`routes/web.php`)

> Back-office **VERGA** (admin / collaborateurs) — interface Inertia + React **dans ce dépôt**.
> Auth session Fortify, pages sous `/admin/*`. **Ne pas exposer via l'API.**

---

## 1. Création de la base de données

Objectif : disposer d'un schéma fiable, documenté et migrable avant tout développement métier.

### 1.1 Cadrage et modélisation

- [ ] Relire et compléter le schéma à partir de `CONTEXTE/Documentation_BDD_VERGA.pdf`
- [ ] Définir les statuts métier (commande, colis, paiement, reversement, réclamation)
- [ ] Valider les relations entre entités (diagramme ER)
- [ ] Choisir la stratégie d'identifiants (UUID recommandé dans la doc)
- [ ] Lister les enums / types (rôles, types d'agence, types d'offre)

### 1.2 Migrations — tables de référence

- [ ] `roles` — rôles et permissions (admin, agence, client, agent)
- [ ] `types_agences` — typologie des agences
- [x] `types_offres` — types d'offre plateforme + `agence_id` nullable (types personnalisés par agence)
- [ ] Adapter / étendre `users` pour le multi-rôle VERGA

### 1.3 Migrations — cœur métier

- [ ] `agences` — entreprises de transit
- [ ] `offres` — offres publiées par les agences
- [ ] `commandes` — achats clients
- [ ] `paiements` — transactions financières
- [ ] `colis` — informations logistiques
- [ ] `historique_colis` — suivi des changements de statut

### 1.4 Migrations — finance et relation client

- [x] `commissions` — commissions VERGA (par commande)
- [x] `configurations_commission` — taux global client / agence (fixe ou pourcentage)
- [ ] `reversements` — reversements aux agences
- [ ] `reclamations` — litiges clients
- [ ] `avis` — notation des agences

### 1.5 Migrations — système

- [ ] `notifications` — notifications in-app
- [ ] `logs` — journal d'audit / actions admin

### 1.6 Contraintes, index et qualité

- [ ] Clés étrangères sur toutes les relations
- [ ] Index sur `email`, `telephone`, champs de statut
- [ ] Factories pour les entités principales
- [ ] Seeders (rôles, admin de test, agences / offres de démo)
- [ ] Vérifier les migrations (`php artisan migrate:fresh --seed`)

---

## 2. Développer le back-office

Objectif : construire l'interface admin (structure, navigation, pages) — d'abord en statique ou avec données factices.

### 2.1 Fondations interface admin

- [x] Définir la structure des routes admin (`/admin/...`)
- [x] Middleware / policy d'accès réservé aux administrateurs
- [x] Layout back-office (sidebar, header, breadcrumbs)
- [x] Navigation principale admin
- [x] Page tableau de bord admin (structure + KPIs placeholder)

### 2.2 Authentification et accès admin

- [x] Connexion admin (réutiliser Fortify ou espace dédié)
- [x] Redirection post-login vers `/admin`
- [x] Gestion session / déconnexion
- [x] Page profil admin (optionnel en V1)

### 2.3 Modules admin — structure des pages

> Chaque module = pages listées ci-dessous, d'abord en UI statique (mock).

- [x] **Agences** — liste, détail, actions (bloquer / supprimer)
- [x] **Offres** — consultation des offres par agence
- [x] **Commandes / achats** — liste des achats clients
- [x] **Colis** — suivi et vérification d'arrivée
- [x] **Paiements** — liste des transactions
- [x] **Reversements** — liste et action de reversement
- [x] **Réclamations** — liste et consultation
- [x] **Collaborateurs** — création de comptes admin / collaborateurs

### 2.4 Composants UI réutilisables

- [x] Tableau de données (tri, pagination UI)
- [x] Filtres et barre de recherche (UI + recherche serveur fonctionnelle sur agences, clients, commandes, colis)
- [x] Modales de confirmation (suppression, blocage)
- [x] Badges de statut (commande, colis, paiement)
- [x] Empty states et messages d'erreur

### 2.5 Exports (préparation UI)

- [x] Boutons export Excel / PDF (UI seulement, logique en phase 3)

---

## 3. Rendre le back-office dynamique

Objectif : connecter chaque écran aux modèles, controllers et règles métier — toujours par petits incréments.

### 3.1 Tableau de bord admin

- [x] KPIs réels (agences actives, commandes, solde paiements, solde commissions, réclamations, reversements)
- [x] Filtre par période (ce mois, mois dernier, trimestre, semestre, année, tout)
- [x] Bar chart : paiements validés par agence (top 10)
- [x] Bar chart : commissions VERGA par agence (top 10)
- [x] Doughnut : commandes par statut
- [x] Récapitulatif financier (paiements / commissions / reversements en attente)

### 3.2 Gestion des agences

- [x] Liste paginée des agences (données BDD, 15/page, react-paginate)
- [x] Fiche détail agence (infos, gérant, stats, offres, commandes récentes)
- [x] Bloquer / débloquer un compte agence (PATCH + toast confirmation)
- [x] Supprimer un compte agence (DELETE + redirect + toast)
- [x] Filtres (statut, recherche serveur avec debounce 350ms) — correction `paginationMeta` + `DataTable` (recherche fonctionnelle)
- [x] Flash toasts (success/error) via HandleInertiaRequests + useFlashToast

### 3.3 Consultation offres et colis

- [x] Liste des offres par agence (paginée, recherche, filtre statut)
- [x] Liste des colis expédiés par agence (paginée, recherche, filtre statut)
- [x] Vérification / confirmation d'arrivée d'un colis (PATCH statut + HistoriqueColis)
- [x] Historique colis sur la fiche commande (page show + timeline)
- [x] Statut initial colis `chez_client` à la commande (API checkout) + flux `chez_client` → `déposé` → … (admin, API agence, fiche détail)

### 3.4 Commandes et paiements

- [x] Liste des achats clients (paginée, recherche code / client / agence, filtre statut)
- [~] Détail commande (client, offre, montant, statut) — **en attente de validation**
- [x] Liste des paiements (paginée, recherche référence, filtre statut)
- [x] Action admin : vérifier le statut d'un paiement via Bamboo Pay (réf. Bamboo ou code VERGA)
- [~] Lien commande ↔ paiement ↔ commission — **en attente de validation**
- [x] Page retour paiement Bamboo (`/paiement/{code}/retour`) — récap Inertia + facture PDF + URL dynamique + middleware normalisation paramètres Bamboo

### 3.5 Reversements et gains

- [x] Liste des reversements (paginée, recherche agence, filtre statut)
- [x] Action admin : effectuer un reversement (PATCH + admin_id + effectue_le + toast)
- [x] Mise à jour des statuts reversement (en_attente → effectué)

### 3.6 Réclamations

- [x] Liste des réclamations (paginée, recherche client, filtre statut)
- [x] Détail réclamation (client, agence, commande, description complète)
- [x] Changement de statut / traitement (workflow ouverte → en_cours → résolue | fermée)

### 3.7 Collaborateurs admin

- [x] Liste paginée avec recherche + badge rôle + date création
- [x] Création de compte collaborateur (formulaire : nom, email, rôle, mot de passe + confirmation)
- [x] Suppression (DELETE + guard compte propre + toast)

### 3.8 Exports et notifications

- [ ] Export Excel (agences, commandes, paiements — par module)
- [ ] Export PDF (idem)
- [ ] Notifications email admin (achat, réclamation — incrément par événement)
- [ ] Notification WhatsApp admin (phase ultérieure si intégration non prête)

### 3.8 bis Configuration commissions

- [~] Page admin commissions globales (client + agence, fixe / pourcentage) — **en attente de validation**
- [ ] Application automatique des commissions à la validation d'un paiement

### 3.9 Qualité et tests

- [ ] Tests Feature par module admin critique
- [ ] Policies / autorisations vérifiées
- [ ] Revue des N+1 et index sur les listes

---

# Phase 2 — API applications externes (`routes/api.php`)

> Endpoints JSON consommés par des **applications hors de ce dépôt** :
> - Back-office **agence** (Angular)
> - App **client** mobile / web (futur)
> - Webhooks et intégrations tierces
>
> Auth **Sanctum** (Bearer token). **Ne pas dupliquer ici ce qui existe déjà en web admin.**

### 2.1 Cadrage API

- [x] Choisir l'authentification API (Sanctum / tokens)
- [x] Documentation OpenAPI / Swagger UI (`/api/documentation`)
- [ ] Rédiger la spec OpenAPI complète (schémas détaillés des réponses)

### 2.2 Implémentation — Agence

- [~] **Auth agence** (inscription, connexion, profil, déconnexion, mot de passe) — **en attente de validation**
- [~] **Métier agence** (offres, commandes, colis, réclamations, paiements) — **en attente de validation**
- [x] **Types d'offre agence** — CRUD types personnalisés (`agence_id` sur `types_offres`, API + Swagger + tests)
- [~] **Clients** — table `clients`, admin consultation (web), API inscription/métier (app externe) — **en attente de validation**
- [ ] Endpoints client avancés (avis, recherche offres, commande)
- [~] Service Bamboo Pay (redirect, instant, statut GET, callback, page retour marchand) — **en attente de validation**
- [ ] Branchement paiement commande + commissions sur callback Bamboo Pay
- [ ] Endpoints admin (si nécessaire côté API)
- [ ] Documentation et tests API (autres modules)

### 2.3 Implémentation — Client API

- [x] Quantités formatées avec unité type d'offre (`quantite_label`, `QuantiteFormatter`, checkout, statut paiement)
- [x] Liste paiements simplifiée (code, montant net, date, `bamboo_reference`, `commande_code` — sans commission)
- [x] Colis : photos renvoyées en liste et détail (`photos[]` avec `url`)
- [x] Commandes : client invité exposé via `CommandeClientPresenter` (plus de `client: null`)

---

## Backlog

Fonctionnalités validées en conception mais **non planifiées pour l’implémentation immédiate**.

### Profilage multi-utilisateurs agence (équipe & permissions)

**Contexte** : aujourd’hui 1 agence = 1 compte gérant (`agences.user_id`). Le rôle `agent_agence` existe en base mais n’est pas exploité. Le back-office agence (Angular) doit permettre à une agence de créer des utilisateurs avec des accès limités.

**Objectif** : permettre au gérant de créer des agents (opérations, commercial, finance…) avec des droits différenciés sur l’API agence.

#### Modèle de données envisagé

- [ ] Table **`agence_membres`** — pivot `user_id` + `agence_id` + profil + statut (`actif` / `suspendu`) + `est_proprietaire`
- [ ] Contrainte unique `(agence_id, user_id)`
- [ ] Migration des gérants existants → ligne `agence_membres` (profil `gerant`, `est_proprietaire = true`)
- [ ] Phase 2 (optionnel) : table **`agence_profils`** pour profils personnalisables par agence

#### Profils MVP (enum ou config)

- [ ] **Gérant** — accès total + gestion des membres
- [ ] **Opérations** — colis (suivi, changement statut), commandes (lecture)
- [ ] **Commercial** — offres (CRUD), commandes (lecture)
- [ ] **Finance** — paiements, reversements (lecture)

#### Permissions (config PHP en V1)

- [ ] Fichier `config/agence-permissions.php` — mapping profil → permissions (`colis.view`, `colis.update_statut`, `offres.create`, `membres.manage`, etc.)
- [ ] Middleware / Gate `agence.permission` sur les routes API agence

#### API à prévoir (`routes/api/agence.php`)

- [ ] `GET /agence/me` enrichi (profil + liste permissions)
- [ ] `GET /agence/membres` — liste l’équipe
- [ ] `POST /agence/membres` — créer / inviter un agent
- [ ] `PATCH /agence/membres/{membre}` — changer profil ou statut
- [ ] `DELETE /agence/membres/{membre}` — retirer un membre
- [ ] `GET /agence/profils` — profils disponibles (pour formulaires Angular)

#### Refactoring technique

- [ ] Remplacer `$user->agence` (HasOne gérant) par résolution via `agence_membres`
- [ ] Adapter `EnsureUserIsAgence` pour gérant + `agent_agence` membre actif
- [ ] Adapter `AgenceApiController::agence()` — scope toujours sur l’agence du membre
- [ ] Policies par ressource (colis, offres, commandes…) avec double vérif : appartenance agence + permission

#### Front Angular (hors ce dépôt)

- [ ] Menu dynamique selon permissions retournées par `/agence/me`
- [ ] Module gestion d’équipe (réservé au gérant)

#### Documentation & tests

- [ ] Swagger — nouveaux endpoints membres / profils
- [ ] Tests Feature : CRUD membres, refus accès sans permission, isolation entre agences

**Référence** : même pattern mental que les **Collaborateurs admin VERGA** (`/admin/collaborateurs`), mais scopé par `agence_id`.

**Priorité** : moyenne — après validation des modules API agence actuels (auth, offres, colis, paiements).

---

## Journal de suivi

> **Dernière session** : 2026-07-07 — poste **PPVTSGA006** — quantités unité, retour Bamboo, API paiements, recherches admin, client invité, CRUD types d'offre agence

| Date | Poste | Module | Action | Statut |
|------|-------|--------|--------|--------|
| 2026-06-13 | — | — | Création du document de suivi | `[x]` |
| 2026-06-13 | — | BDD | Migrations + modèles (13 tables métier + extension users) | `[x]` |
| 2026-06-13 | — | Admin | §2.1 Fondations back-office (routes, middleware, layout, nav, dashboard) | `[x]` |
| 2026-06-13 | — | Admin | §2.2 Auth Fortify + redirection `/admin/dashboard` + URL dev auto-login | `[x]` |
| 2026-06-13 | — | Admin | §2.4 Composants UI réutilisables (DataTable, StatusBadge, EmptyState, ConfirmDialog) | `[x]` |
| 2026-06-13 | — | Admin | §2.3 8 pages modules admin avec données mock (agences → collaborateurs) | `[x]` |
| 2026-06-13 | — | Admin | §2.5 Boutons export Excel/PDF (UI placeholder, disabled) sur 5 modules | `[x]` |
| 2026-06-13 | — | Admin | §3.4 Détail commande + liens paiement/commission/colis (show + bouton Voir) | `[~]` validation |
| 2026-06-21 | — | API | Auth agence Sanctum : login, me, logout, password (9 tests) | `[~]` validation |
| 2026-06-22 | — | Clients | Table clients + admin lecture seule + API register/profile/métier (20 tests) | `[~]` validation |
| 2026-06-22 | — | API | Swagger (L5-Swagger) + doc OpenAPI agence + client (28 endpoints) | `[x]` |
| 2026-06-23 | — | Admin | Configuration commissions globales (page /admin/commissions, 6 tests) | `[~]` validation |
| 2026-06-24 | — | API Client | Checkout commande (invité/connecté) + settlement Bamboo + capacité offres (7 tests) | `[~]` validation |
| 2026-07-05 | — | Backlog | Profilage multi-utilisateurs agence (`agence_membres`, profils, permissions) — conception documentée | `[ ]` backlog |
| 2026-07-07 | **PPVTSGA006** | Ops / Prod | Diagnostic erreur 500 API checkout : package Saloon absent (`composer install` requis sur le serveur) | `[x]` |
| 2026-07-07 | **PPVTSGA006** | Admin | Page paiements : bouton « Vérifier » (Bamboo Pay via `bamboo_reference` ou code VERGA) + route `PATCH /admin/paiements/{paiement}/verifier-statut` | `[x]` |
| 2026-07-07 | **PPVTSGA006** | API Client | Vérification statut paiement : lookup Bamboo avec `bamboo_reference` sinon `code` VERGA (`CommandeCheckoutService`) | `[x]` |
| 2026-07-07 | **PPVTSGA006** | Colis | Nouveau statut `chez_client` (colis encore chez le client) — migration, checkout API, flux admin/API agence, UI liste + fiche détail (5 étapes) | `[x]` |
| 2026-07-07 | **PPVTSGA006** | API | Quantités avec libellé unité (`quantite_label`, type d'offre) — client, agence, checkout, OpenAPI v1.2 | `[x]` |
| 2026-07-07 | **PPVTSGA006** | Paiements | URL retour Bamboo dynamique (`PaiementReturnUrl`), page récap `/paiement/{code}/retour`, facture PDF, middleware `NormalizeBambooPayReturnUrl` | `[x]` |
| 2026-07-07 | **PPVTSGA006** | API | Liste paiements client/agence allégée (montant net, références uniquement — sans commission) | `[x]` |
| 2026-07-07 | **PPVTSGA006** | Admin | Barres de recherche fonctionnelles — agences, clients, commandes, colis (`paginationMeta` + fix `DataTable`) | `[x]` |
| 2026-07-07 | **PPVTSGA006** | API | Commandes : `client` renseigné pour commandes invité (`CommandeClientPresenter`) | `[x]` |
| 2026-07-07 | **PPVTSGA006** | API Agence | CRUD types d'offre personnalisés — migration `agence_id`, 5 endpoints, Swagger, `OffreTypeResolver` | `[x]` |

---

## Notes et décisions

- **Ordre** : Web admin interne d'abord, API apps externes en parallèle ou ensuite.
- **Séparation stricte** : `web.php` = admin VERGA (React) · `api.php` = agence Angular + apps externes. Ne pas mélanger.
- **Découpage** : ne pas livrer un module entier d'un bloc ; cocher les sous-tâches une par une.
- **Validation** : chaque fonctionnalité doit être validée par le client avant de passer à la suivante.
- **API auth** : Sanctum Bearer token, préfixe `/api/v1/agence`, login via email du gérant (`users.email`).
- **Colis — flux logistique** : `chez_client` (création à la commande API) → `déposé` (dépôt agence) → `en_transit` → `arrivé` → `récupéré`. Le paiement validé ne fait pas avancer le statut colis automatiquement.
- **Paiements admin** : vérification manuelle Bamboo Pay possible depuis `/admin/paiements` (paiements `en_attente`).
- **Retour Bamboo Pay** : URL envoyée à Bamboo = `{APP_URL}/paiement/{code}/retour?ref={code}` ; middleware corrige les redirections mal formées (`&status=...` sans `?`).
- **API paiements (listes)** : champs `code`, `montant` (net transport), `created_at`, `bamboo_reference`, `commande_code` uniquement.
- **Types d'offre** : types plateforme (`agence_id` null) + types créés par chaque agence (slug unique par agence) ; CRUD API `/api/v1/agence/types-offres`.
- **Commandes invité** : objet `client` rempli depuis `nom` / `prenom` / `telephone` de la commande si pas de `client_id`.
- **Références** : `CONTEXTE/DOCUMENT_DESCRIPTIF_DE_VERGA.pdf`, `CONTEXTE/Documentation_BDD_VERGA.pdf`.

---

## Prochaine action suggérée

**Déployer sur le serveur** : `composer install`, `php artisan migrate` (migrations `chez_client`, `bamboo_message`, `agence_id` sur `types_offres`), `php artisan l5-swagger:generate`, `npm run build`, puis **valider l'API agence** (types d'offre CRUD, offres avec type perso, listes paiements) et les **recherches admin**.
