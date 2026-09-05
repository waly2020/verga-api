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
- [x] `configurations_commission` — taux global client / agence (fixe, pourcentage, **ou grille** côté client)
- [x] `villes` — villes (pays en champ texte, code unique) utilisées par les destinations
- [x] `configurations_publicite` — tarif publicité (prix/jour entier FCFA + frais fixe ou %)
- [x] `publicites` — campagnes agence / client / VERGA (`offre_id` optionnel)
- [x] `paiements_publicite` — paiements Bamboo dédiés (préfixe `PUB-`)
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
- [x] **Villes** — référentiel ville + nom de pays + code
- [x] **Destinations** — trajets composés de deux localités + config tarifaire optionnelle
- [x] **Publicités** — modération, création interne, tarif
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

- [~] Page admin commissions globales (client + agence, fixe **ou** pourcentage unique) — **en attente de validation**
- [x] Application automatique de la commission **client** à chaque versement (`OrderPricingService`) puis cumul commande à la validation Bamboo
- [x] Grille tarifaire par tranches de montant (sous-total du versement, frais fixes, libellé optionnel)

### 3.9 Qualité et tests

- [ ] Tests Feature par module admin critique
- [x] Tests Feature publicités admin (tarif, modération, création interne, changement statut — 15 tests)
- [ ] Policies / autorisations vérifiées
- [ ] Revue des N+1 et index sur les listes

### 3.10 Destinations et localités

- [x] CRUD admin `/admin/villes` — villes (nom de pays en champ, pas de table pays)
- [x] CRUD admin `/admin/destinations` — trajet = `ville_depart_id` + `ville_arrivee_id`
- [x] Si `appliquer_configuration` : prix offre forcé serveur + commission **agence** = % destination
- [x] Sinon : prix offre libre + commission agence globale
- [x] Pivot `agence_destination` — rattachement agence (API find-or-create-attach)
- [x] Offres via `destination_id` uniquement (plus d’origine/arrivée libres)
- [x] UI admin + Swagger alignés sur les villes (`VilleResource`, `label` du trajet)

### 3.11 Publicités

- [x] Tarif admin `/admin/publicites/configuration` — prix **par jour** et frais (fixe ou %), **entiers FCFA**
- [x] Liste + modération : changement de statut admin (valider, refuser avec motif, retirer, republier selon transitions)
- [x] Statut `retirée` — l’admin peut retirer une pub à tout moment ; republication si payée et dates valides
- [x] Création admin : publicité interne VERGA, ou pour une agence / un client — **publiée immédiatement**, sans paiement
- [x] Durée inclusive : `(date_fin - date_debut) + 1` jours
- [x] Propriétaire : `agence_id` **ou** `client_id` (jamais les deux) ; pubs VERGA : les deux nuls
- [x] Agence : offre rattachable uniquement si elle lui appartient ; client : pas d’offre
- [x] Parcours agence/client : `en_attente` → validation admin → paiement Bamboo dédié → `publiée` / `payé` ; refus → modification + resoumission
- [x] Expiration automatique quand `date_fin` est dépassée (`expirée`)

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
- [x] **Pays agence** — `GET /agence/pays` (noms distincts des villes actives)
- [x] **Villes agence** — `GET /agence/villes?pays=`
- [x] **Destinations agence** — `GET /agence/destinations`, `GET …/paginated`, `POST` find-or-create-attach (`ville_depart_id` + `ville_arrivee_id`)
- [x] **Publicités agence** — CRUD, resoumettre, paiement Bamboo dédié, lecture tarif
- [~] **Clients** — table `clients`, admin consultation (web), API inscription/métier (app externe) — **en attente de validation**
- [ ] Endpoints client avancés (avis, recherche offres, commande)
- [~] Service Bamboo Pay (redirect, instant, statut GET, callback, page retour marchand) — **en attente de validation**
- [x] Paiement publicité isolé (callback `POST /payments/bamboo-pay/publicites/callback` — ne pas toucher au settlement commandes)
- [ ] Branchement paiement commande + commissions sur callback Bamboo Pay
- [ ] Endpoints admin (si nécessaire côté API)
- [ ] Documentation et tests API (autres modules)

### 2.3 Implémentation — Client API

- [x] Quantités formatées avec unité type d'offre (`quantite_label`, `QuantiteFormatter`, checkout, statut paiement)
- [x] Liste paiements simplifiée (code, montant net, date, `bamboo_reference`, `commande_code` — sans commission)
- [x] Colis : photos renvoyées en liste et détail (`photos[]` avec `url`)
- [x] Commandes : client invité exposé via `CommandeClientPresenter` (plus de `client: null`)
- [x] **Publicités client** — CRUD, resoumettre, paiement (pas d’offre rattachable)
- [x] Catalogue public `GET /api/v1/publicites` (pubs `publiée` dans les dates)
- [x] Statut paiement pub `GET /api/v1/publicites/paiements/{code}/statut`
- [x] Page retour `/publicite-paiement/{code}/retour`

---

## Backlog

Fonctionnalités validées en conception mais **non planifiées pour l’implémentation immédiate**.

### Grille tarifaire des commissions (2026-09-05)

**Livré** : type `grille` sur la config **client** uniquement. Table `commission_paliers` (`montant_min`, `montant_max` nullable, `frais`, `libelle` nullable).

Au paiement, le **sous-total du versement** choisit la tranche. La commission **agence** reste fixe ou %.

- [x] Tranches continues, dernière ouverte
- [x] Client only
- [x] Base = sous-total du versement
- [x] Frais fixes par tranche, libellé optionnel
- [x] Admin `/admin/commissions` + `OrderPricingService` + estimation + OpenAPI v1.8

---

### Comptes et rôles agence (refonte 2026-07-17)

**Architecture cible** :
- `users` : comptes internes VERGA (admin, collaborateur) et clients uniquement.
- `agence_users` : tous les comptes back-office agence (propriétaire + employés), authentification Sanctum.
- `agence_roles` : rôles définis par l’admin VERGA (`admin-agence` système + rôles métier).
- **Pas de restriction API par rôle** : Angular utilise `role.slug`, `role.nom`, `role.description` pour l’affichage uniquement.

#### Modèle de données

- [x] Tables `agence_roles` et `agence_users`
- [x] Schéma initial propre : aucune relation `agences.user_id` et aucun rôle agence dans `users`
- [x] Historique colis polymorphe dès sa création (`actor_type` / `actor_id`)
- [x] Aucune migration de reprise des anciennes données agence (environnement de développement)

#### API agence (`routes/api/agence.php`)

- [x] `GET /agence/roles` — rôles actifs (lecture seule)
- [x] `GET|POST|PATCH|DELETE /agence/users` — gestion équipe par l’agence
- [x] Auth register/login/me sur `AgenceUser` (rôle `admin-agence` à l’inscription)

#### Admin VERGA

- [x] CRUD `/admin/agence-roles` (middleware `admin.strict`, réservé `users.role = admin`)
- [x] Page React `admin/agence-roles/index`
- [x] CRUD `/admin/agence-users` pour les collaborateurs agence (création, rôle, statut, suppression)
- [x] Menu déroulant « Agences » : Agences, Rôles agence, Utilisateurs agence

#### Refactoring technique

- [x] `EnsureUserIsAgence` → `instanceof AgenceUser`
- [x] `ColisStatutService` + `historique_colis` : acteur polymorphe (`actor_type` / `actor_id`)
- [x] OpenAPI v1.4 — schémas `AgenceRoleResource`, `AgenceUserResource`, endpoints `/roles` et `/users`

#### Front Angular (hors ce dépôt)

- [ ] Menu / libellés selon `user.role` retourné par `/agence/me`
- [ ] Module gestion utilisateurs agence (assignation rôle)

#### Documentation & tests

- [x] Swagger rôles / utilisateurs agence
- [x] Tests Feature : auth, CRUD users, rôles admin, isolation, accès API identique par rôle

**Référence** : rôles agence gérés comme les **Types d’offre** admin (CRUD Inertia, slug stable).

---

### ~~Profilage multi-utilisateurs agence (équipe & permissions)~~ — remplacé par refonte ci-dessus

**Contexte historique** : ancienne approche `agence_membres` + profils statiques + middleware `agence.permission` — **supprimée** le 2026-07-17.

#### Modèle de données envisagé (obsolète)

- [x] ~~Table **`agence_membres`**~~ → remplacée par `agence_users`
- [x] Contrainte unique `(agence_id, user_id)` + unique `user_id`
- [x] Migration des gérants existants
- [ ] Phase 2 (optionnel) : table **`agence_profils`** pour profils personnalisables par agence

#### Profils MVP (enum ou config)

- [x] **Gérant**
- [x] **Opérations**
- [x] **Commercial**
- [x] **Finance**

#### Permissions (config PHP en V1)

- [x] Fichier `config/agence-permissions.php`
- [x] Middleware `agence.permission`

#### API à prévoir (`routes/api/agence.php`)

- [x] `GET /agence/me` enrichi (profil + liste permissions)
- [ ] `GET /agence/membres` — liste l’équipe
- [x] `POST /agence/membres` — créer un agent
- [x] `PATCH /agence/membres/{membre}` — changer profil ou statut
- [x] `DELETE /agence/membres/{membre}` — retirer un membre
- [x] `GET /agence/profils` — profils disponibles (pour formulaires Angular)

#### Refactoring technique

- [x] Remplacer `$user->agence` (HasOne gérant) par résolution via `agence_membres`
- [x] Adapter `EnsureUserIsAgence` pour gérant + `agent_agence` membre actif
- [ ] Adapter `AgenceApiController::agence()` — scope toujours sur l’agence du membre
- [x] Permissions par route (middleware `agence.permission`)

#### Front Angular (hors ce dépôt)

- [ ] Menu dynamique selon permissions retournées par `/agence/me`
- [ ] Module gestion d’équipe (réservé au gérant)

#### Documentation & tests

- [x] Swagger membres / profils
- [x] Tests Feature CRUD + permissions + isolation

**Référence** : même pattern mental que les **Collaborateurs admin VERGA** (`/admin/collaborateurs`), mais scopé par `agence_id`.

**Priorité** : moyenne — après validation des modules API agence actuels (auth, offres, colis, paiements).

---

## Journal de suivi

> **Dernière session** : 2026-09-05 — grille tarifaire commissions client (tranches sur le sous-total du versement)

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
| 2026-07-05 | — | Backlog | Profilage multi-utilisateurs agence — conception documentée | `[x]` implémenté 2026-07-17 |
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
| 2026-07-17 | — | API Agence | Refonte comptes/rôles — `agence_users`, `agence_roles`, auth Sanctum dédiée, CRUD `/users`, admin CRUD rôles, migration données | `[x]` |
| 2026-07-17 | — | API Agence | ~~Équipe multi-utilisateurs — `agence_membres`…~~  remplacé par refonte ci-dessus | `[x]` |
| 2026-08-18 | — | Destinations | Destinations partagées + pivot agence, config tarifaire optionnelle, API agence, offres via `destination_id` | `[x]` |
| 2026-08-18 | — | Publicités | Tables + cycle de vie (attente → validation/refus → paiement → publiée / expirée), tarif admin, APIs agence/client/catalogue | `[x]` |
| 2026-08-18 | — | Publicités | Paiement Bamboo dédié (`PUB-`, callback isolé, page retour) | `[x]` |
| 2026-08-19 | — | Publicités | Montants **entiers FCFA** (plus de `step` HTML 0,01) | `[x]` |
| 2026-08-19 | — | Admin | Création publicité interne VERGA / agence / client — publiée sans paiement (13 tests) | `[x]` |
| 2026-08-22 | — | Admin | Changement statut publicité (retirée, republication) — dialogue admin + transitions (15 tests) | `[x]` |
| 2026-09-04 | — | Destinations | Table `pays` (ville, pays, code) ; destinations = `pays_depart_id` / `pays_arrivee_id` ; drop `depart` / `arrivee` | `[x]` |
| 2026-09-04 | — | Admin | CRUD `/admin/pays`, formulaires destinations/offres, listes trajets par localités | `[x]` |
| 2026-09-04 | — | API | `GET /agence/pays`, `DestinationResource` (`pays_depart` / `pays_arrivee` / `label`), OpenAPI v1.6 | `[x]` |
| 2026-09-04 | — | Backlog | Grille tarifaire commissions (tranches de montant, ex. 0–9 999 → 1 500 FCFA) | `[x]` |
| 2026-09-05 | — | Villes | Table `villes` (plus de table `pays`) ; pays = champ réutilisé ; admin `/admin/villes` ; API `GET /agence/pays` + `GET /agence/villes` | `[x]` |
| 2026-09-05 | — | Commissions | Grille client : `commission_paliers`, type `grille`, admin `/admin/commissions`, estimation OpenAPI v1.8 | `[x]` |
| 2026-09-05 | — | Offres | Job `DesactiverOffresDepartPassees` en file database, cron 23:59 Africa/Libreville | `[x]` |

---

## Notes et décisions

- **Ordre** : Web admin interne d'abord, API apps externes en parallèle ou ensuite.
- **Séparation stricte** : `web.php` = admin VERGA (React) · `api.php` = agence Angular + apps externes. Ne pas mélanger.
- **Découpage** : ne pas livrer un module entier d'un bloc ; cocher les sous-tâches une par une.
- **Validation** : chaque fonctionnalité doit être validée par le client avant de passer à la suivante.
- **API auth agence** : Sanctum Bearer token ; comptes dans `agence_users` ; rôle retourné via `GET /agence/me` (`role.slug`, `role.nom`, `role.description`) — **sans permissions API** ; rôles créés par admin VERGA (`agence_roles`).
- **Colis — flux logistique** : `chez_client` (création à la commande API) → `déposé` (dépôt agence) → `en_transit` → `arrivé` → `récupéré`. Le paiement validé ne fait pas avancer le statut colis automatiquement.
- **Paiements admin** : vérification manuelle Bamboo Pay possible depuis `/admin/paiements` (paiements `en_attente`).
- **Retour Bamboo Pay** : URL envoyée à Bamboo = `{APP_URL}/paiement/{code}/retour?ref={code}` ; middleware corrige les redirections mal formées (`&status=...` sans `?`).
- **API paiements (listes)** : champs `code`, `montant` (net transport), `created_at`, `bamboo_reference`, `commande_code` uniquement.
- **Types d'offre** : types plateforme (`agence_id` null) + types créés par chaque agence (slug unique par agence) ; CRUD API `/api/v1/agence/types-offres`.
- **Commandes invité** : objet `client` rempli depuis `nom` / `prenom` / `telephone` de la commande si pas de `client_id`.
- **Destinations** : table partagée `destinations` + pivot `agence_destination`. Un trajet = deux villes (`ville_depart_id` / `ville_arrivee_id`). Le pays est un champ de `villes`, pas une table. Plus de champs texte `depart` / `arrivee`. Config destination : si `appliquer_configuration` → prix offre forcé + commission **agence** % destination ; sinon prix libre + commission agence globale.
- **Commissions client** : fixe, pourcentage, **ou grille** (`commission_paliers`) selon le sous-total de **chaque versement**, puis cumulée à la validation. Libellé de tranche optionnel. La config destination n’affecte pas la commission client.
- **Publicités** : indépendantes d’une offre (`offre_id` nullable). Propriétaire = agence **xor** client, ou aucun des deux (pub VERGA créée par l’admin).
- **Publicités — parcours payant** (agence/client) : création `en_attente` / `non_payé` → admin valide ou refuse → si validée, paiement Bamboo **dédié** (ne pas réutiliser le settlement commandes) → `publiée` / `payé`. Durée inclusive `(date_fin − date_debut) + 1`.
- **Publicités — admin** : création directe → `publiée` + `payé` (pas de Bamboo). Modération : transitions `en_attente` → validée/refusée/retirée ; retrait depuis `publiée` ; republication depuis `retirée`/`expirée` si payée et `date_fin` ≥ aujourd’hui. Tarif : prix/jour et frais en **entiers** (FCFA, pas de centimes).
- **Publicités — affichage site** : `GET /api/v1/publicites` ; emplacement visuel = front Angular.
- **Références** : `CONTEXTE/DOCUMENT_DESCRIPTIF_DE_VERGA.pdf`, `CONTEXTE/Documentation_BDD_VERGA.pdf`.

---

## Prochaine action suggérée

`php artisan migrate:fresh` puis configurer la grille client dans `/admin/commissions` (type Grille tarifaire). Vérifier une estimation `GET /client/offres/{id}/estimation?quantite=`.
