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
- [ ] Adapter / étendre `users` pour le multi-rôle VERGA

### 1.3 Migrations — cœur métier

- [ ] `agences` — entreprises de transit
- [ ] `offres` — offres publiées par les agences
- [ ] `commandes` — achats clients
- [ ] `paiements` — transactions financières
- [ ] `colis` — informations logistiques
- [ ] `historique_colis` — suivi des changements de statut

### 1.4 Migrations — finance et relation client

- [ ] `commissions` — commissions VERGA
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
- [x] Filtres et barre de recherche (UI)
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
- [x] Filtres (statut, recherche serveur avec debounce 350ms)
- [x] Flash toasts (success/error) via HandleInertiaRequests + useFlashToast

### 3.3 Consultation offres et colis

- [x] Liste des offres par agence (paginée, recherche, filtre statut)
- [x] Liste des colis expédiés par agence (paginée, recherche, filtre statut)
- [x] Vérification / confirmation d'arrivée d'un colis (PATCH statut + HistoriqueColis)
- [x] Historique colis sur la fiche commande (page show + timeline)

### 3.4 Commandes et paiements

- [x] Liste des achats clients (paginée, recherche code, filtre statut)
- [~] Détail commande (client, offre, montant, statut) — **en attente de validation**
- [x] Liste des paiements (paginée, recherche référence, filtre statut)
- [~] Lien commande ↔ paiement ↔ commission — **en attente de validation**

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

- [~] **Auth agence** (connexion, profil, déconnexion, mot de passe) — **en attente de validation**
- [~] **Métier agence** (offres, commandes, colis, réclamations, paiements) — **en attente de validation**
- [~] **Clients** — table `clients`, admin consultation (web), API inscription/métier (app externe) — **en attente de validation**
- [ ] Endpoints client avancés (avis, recherche offres, commande)
- [ ] Endpoints admin (si nécessaire côté API)
- [ ] Documentation et tests API (autres modules)

---

## Journal de suivi

| Date | Module | Action | Statut |
|------|--------|--------|--------|
| 2026-06-13 | — | Création du document de suivi | `[x]` |
| 2026-06-13 | BDD | Migrations + modèles (13 tables métier + extension users) | `[x]` |
| 2026-06-13 | Admin | §2.1 Fondations back-office (routes, middleware, layout, nav, dashboard) | `[x]` |
| 2026-06-13 | Admin | §2.2 Auth Fortify + redirection `/admin/dashboard` + URL dev auto-login | `[x]` |
| 2026-06-13 | Admin | §2.4 Composants UI réutilisables (DataTable, StatusBadge, EmptyState, ConfirmDialog) | `[x]` |
| 2026-06-13 | Admin | §2.3 8 pages modules admin avec données mock (agences → collaborateurs) | `[x]` |
| 2026-06-13 | Admin | §2.5 Boutons export Excel/PDF (UI placeholder, disabled) sur 5 modules | `[x]` |
| 2026-06-13 | Admin | §3.4 Détail commande + liens paiement/commission/colis (show + bouton Voir) | `[~]` validation |
| 2026-06-21 | API | Auth agence Sanctum : login, me, logout, password (9 tests) | `[~]` validation |
| 2026-06-22 | Clients | Table clients + admin lecture seule + API register/profile/métier (20 tests) | `[~]` validation |
| 2026-06-22 | API | Swagger (L5-Swagger) + doc OpenAPI agence + client (28 endpoints) | `[x]` |

---

## Notes et décisions

- **Ordre** : Web admin interne d'abord, API apps externes en parallèle ou ensuite.
- **Séparation stricte** : `web.php` = admin VERGA (React) · `api.php` = agence Angular + apps externes. Ne pas mélanger.
- **Découpage** : ne pas livrer un module entier d'un bloc ; cocher les sous-tâches une par une.
- **Validation** : chaque fonctionnalité doit être validée par le client avant de passer à la suivante.
- **API auth** : Sanctum Bearer token, préfixe `/api/v1/agence`, login via email du gérant (`users.email`).
- **Références** : `CONTEXTE/DOCUMENT_DESCRIPTIF_DE_VERGA.pdf`, `CONTEXTE/Documentation_BDD_VERGA.pdf`.

---

## Prochaine action suggérée

**Valider l'API auth agence** (§ 2.2), puis enchaîner sur les **endpoints agence métier** (offres, commandes, colis).
