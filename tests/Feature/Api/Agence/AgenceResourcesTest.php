<?php

namespace Tests\Feature\Api\Agence;

use App\Models\Colis;
use App\Models\ColisPhoto;
use App\Models\Commande;
use App\Models\Destination;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\TypeOffre;

class AgenceResourcesTest extends AgenceApiTestCase
{
    public function test_agence_can_list_and_create_destinations(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $attached = $this->createDestination([
            'depart' => 'chine',
            'arrivee' => 'libreville',
        ], $agence);

        $global = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'gabon',
            'appliquer_configuration' => true,
            'montant' => 8500,
            'commission_pourcentage' => 2.5,
        ]);

        $inactive = $this->createDestination([
            'depart' => 'inactive',
            'arrivee' => 'ville',
            'actif' => false,
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/destinations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'ville_depart_id',
                    'ville_arrivee_id',
                    'ville_depart',
                    'ville_arrivee',
                    'label',
                    'montant',
                    'commission_pourcentage',
                    'appliquer_configuration',
                    'actif',
                    'rattachee',
                ]],
            ])
            ->assertJsonFragment(['id' => $attached->id, 'rattachee' => true])
            ->assertJsonFragment(['id' => $global->id, 'rattachee' => false])
            ->assertJsonMissing(['id' => $inactive->id]);

        $depart = $this->createVille(['pays' => 'France', 'ville' => 'Paris', 'code' => 'PAR']);
        $arrivee = $this->createVille(['pays' => 'Gabon', 'ville' => 'Port-Gentil', 'code' => 'POG']);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/destinations', [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $arrivee->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.ville_depart.ville', 'Paris')
            ->assertJsonPath('data.ville_arrivee.ville', 'Port-Gentil')
            ->assertJsonPath('data.label', 'Paris (France) → Port-Gentil (Gabon)')
            ->assertJsonPath('data.appliquer_configuration', false)
            ->assertJsonPath('data.rattachee', true);

        $this->assertDatabaseHas('destinations', [
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/destinations')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_agence_can_list_destinations_paginated(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $this->createDestination(['depart' => 'a', 'arrivee' => 'b'], $agence);
        $this->createDestination(['depart' => 'c', 'arrivee' => 'd']);
        $this->createDestination(['depart' => 'e', 'arrivee' => 'f']);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/destinations/paginated?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure([
                'data' => [['id', 'ville_depart', 'ville_arrivee', 'label', 'rattachee']],
                'links',
                'meta',
            ]);
    }

    public function test_agence_destination_creation_deduplicates_and_attaches(): void
    {
        ['agence' => $agenceA, 'token' => $tokenA] = $this->createAuthenticatedAgence([
            'email' => 'agence-a@test.com',
        ]);
        ['agence' => $agenceB, 'token' => $tokenB] = $this->createAuthenticatedAgence([
            'email' => 'agence-b@test.com',
        ]);

        $existing = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'gabon',
            'appliquer_configuration' => true,
            'montant' => 8500,
            'commission_pourcentage' => 2.5,
        ], $agenceA);

        $this->withAgenceToken($tokenB)
            ->postJson('/api/v1/agence/destinations', [
                'ville_depart_id' => $existing->ville_depart_id,
                'ville_arrivee_id' => $existing->ville_arrivee_id,
                'appliquer_configuration' => true,
                'montant' => 1,
                'commission_pourcentage' => 99,
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.appliquer_configuration', true)
            ->assertJsonPath('data.montant', 8500);

        $this->assertSame(1, Destination::query()
            ->where('ville_depart_id', $existing->ville_depart_id)
            ->where('ville_arrivee_id', $existing->ville_arrivee_id)
            ->count());
        $this->assertTrue($existing->fresh()->agences()->where('agences.id', $agenceB->id)->exists());
    }

    public function test_agence_can_create_offre_with_global_destination_and_auto_attaches(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $global = $this->createDestination([
            'depart' => 'tokyo',
            'arrivee' => 'libreville',
        ]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $global->id,
                'titre' => 'Offre globale',
                'type' => 'particulier',
                'prix' => 1000,
                'capacite_totale' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.destination_id', $global->id);

        $this->assertTrue($global->fresh()->agences()->where('agences.id', $agence->id)->exists());
    }

    public function test_agence_cannot_create_offre_with_inactive_destination(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();
        $inactive = $this->createDestination([
            'depart' => 'tokyo',
            'arrivee' => 'libreville',
            'actif' => false,
        ]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $inactive->id,
                'titre' => 'Offre interdite',
                'type' => 'particulier',
                'prix' => 1000,
                'capacite_totale' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['destination_id']);
    }

    public function test_agence_can_list_pays(): void
    {
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);
        $this->createVille([
            'pays' => 'France',
            'ville' => 'Paris',
            'code' => 'PAR',
            'actif' => false,
        ]);

        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Port-Gentil',
            'code' => 'POG',
        ]);

        $this->getJson('/api/v1/agence/pays')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pays', 'Gabon')
            ->assertJsonMissingPath('data.0.code');
    }

    public function test_agence_can_list_villes_filtered_by_pays(): void
    {
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Port-Gentil',
            'code' => 'POG',
        ]);
        $this->createVille([
            'pays' => 'France',
            'ville' => 'Paris',
            'code' => 'PAR',
        ]);

        $this->getJson('/api/v1/agence/villes?pays=Gabon')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.pays', 'Gabon')
            ->assertJsonPath('data.0.ville', 'Libreville')
            ->assertJsonPath('data.1.ville', 'Port-Gentil');
    }

    public function test_agence_can_create_destination_from_villes(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();
        $depart = $this->createVille([
            'pays' => 'Chine',
            'ville' => 'Guangzhou',
            'code' => 'CAN',
        ]);
        $arrivee = $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/destinations', [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $arrivee->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.ville_depart.ville', 'Guangzhou')
            ->assertJsonPath('data.ville_arrivee.ville', 'Libreville')
            ->assertJsonPath('data.ville_depart_id', $depart->id)
            ->assertJsonPath('data.ville_arrivee.code', 'LBV')
            ->assertJsonPath('data.rattachee', true);

        $this->assertDatabaseHas('destinations', [
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
        ]);
    }

    public function test_agence_cannot_attach_inactive_destination_via_store(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();

        $inactive = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'gabon',
            'actif' => false,
        ]);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/destinations', [
                'ville_depart_id' => $inactive->ville_depart_id,
                'ville_arrivee_id' => $inactive->ville_arrivee_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ville_depart_id']);
    }

    public function test_agence_forces_prix_when_destination_has_configuration(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $destination = $this->createDestination([
            'depart' => 'chine',
            'arrivee' => 'libreville',
            'appliquer_configuration' => true,
            'montant' => 8750,
            'commission_pourcentage' => 10,
        ], $agence);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $destination->id,
                'titre' => 'Offre forcée',
                'type' => 'particulier',
                'prix' => 1,
                'capacite_totale' => 100,
            ])
            ->assertCreated()
            ->assertJsonPath('data.prix', 8750);

        $this->assertDatabaseHas('offres', [
            'titre' => 'Offre forcée',
            'prix' => 8750,
        ]);
    }

    public function test_agence_can_create_offre_with_paliers(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $destination = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'libreville',
        ], $agence);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $destination->id,
                'titre' => 'Offre paliers agence',
                'type' => 'particulier',
                'prix' => 3000,
                'paliers' => [
                    ['min' => 1, 'max' => 2, 'prix' => 3000],
                    ['min' => 3, 'max' => null, 'prix' => 2000],
                ],
                'capacite_totale' => 80,
            ])
            ->assertCreated()
            ->assertJsonPath('data.paliers.0.prix', 3000)
            ->assertJsonPath('data.paliers.1.max', null)
            ->assertJsonPath('data.paliers.1.prix', 2000);

        $offre = Offre::query()->where('titre', 'Offre paliers agence')->firstOrFail();
        $this->assertTrue($offre->hasPaliers());
        $this->assertSame($agence->id, $offre->agence_id);
    }

    public function test_agence_can_list_and_create_offres(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $this->createOffreForAgence($agence, [
            'titre' => 'Offre existante',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/offres')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'titre',
                    'description',
                    'type',
                    'type_offre_id',
                    'prix',
                    'capacite_totale',
                    'capacite_disponible',
                    'destination_id',
                    'destination' => [
                        'id',
                        'ville_depart',
                        'ville_arrivee',
                        'montant',
                        'commission_pourcentage',
                        'appliquer_configuration',
                    ],
                    'statut',
                    'created_at',
                    'updated_at',
                ]],
            ]);

        $destination = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'libreville',
        ], $agence);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $destination->id,
                'titre' => 'Nouvelle offre',
                'type' => 'conteneur',
                'prix' => 225000,
                'capacite_totale' => 1,
                'description' => 'Conteneur complet',
            ])
            ->assertCreated()
            ->assertJsonPath('data.titre', 'Nouvelle offre')
            ->assertJsonPath('data.type', 'conteneur')
            ->assertJsonPath('data.destination_id', $destination->id)
            ->assertJsonStructure(['data' => ['type_offre_id', 'type_offre', 'destination']]);

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'destination_id' => $destination->id,
            'titre' => 'Nouvelle offre',
            'type' => 'conteneur',
        ]);
    }

    public function test_agence_can_create_offre_with_type_offre_id(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $typeOffre = TypeOffre::query()->where('slug', 'metre_cube')->firstOrFail();
        $destination = $this->createDestination([
            'depart' => 'libreville',
            'arrivee' => 'paris',
        ], $agence);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $destination->id,
                'titre' => 'Offre m³',
                'type_offre_id' => $typeOffre->id,
                'prix' => 15000,
                'capacite_totale' => 50,
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'metre_cube')
            ->assertJsonPath('data.type_offre_id', $typeOffre->id)
            ->assertJsonPath('data.destination_id', $destination->id);

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'destination_id' => $destination->id,
            'titre' => 'Offre m³',
            'type' => 'metre_cube',
            'type_offre_id' => $typeOffre->id,
        ]);
    }

    public function test_agence_can_create_offre_capacite_illimitee(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $destination = $this->createDestination([
            'depart' => 'libreville',
            'arrivee' => 'port-gentil',
        ], $agence);

        $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/offres', [
                'destination_id' => $destination->id,
                'titre' => '2000 F / colis Port-Gentil',
                'type' => 'particulier',
                'prix' => 2000,
                'capacite_illimitee' => true,
                'date_depart' => '2026-07-20',
                'date_depot_colis' => '2026-07-19',
            ])
            ->assertCreated()
            ->assertJsonPath('data.capacite_illimitee', true)
            ->assertJsonPath('data.capacite_totale', null)
            ->assertJsonPath('data.capacite_disponible', null)
            ->assertJsonPath('data.date_depart', '2026-07-20')
            ->assertJsonPath('data.date_depot_colis', '2026-07-19')
            ->assertJsonPath('data.destination_id', $destination->id);

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'destination_id' => $destination->id,
            'titre' => '2000 F / colis Port-Gentil',
            'capacite_illimitee' => true,
            'capacite_totale' => null,
            'capacite_disponible' => null,
        ]);

        $offre = Offre::where('titre', '2000 F / colis Port-Gentil')->firstOrFail();
        $this->assertSame('2026-07-20', $offre->date_depart?->toDateString());
        $this->assertSame('2026-07-19', $offre->date_depot_colis?->toDateString());
    }

    public function test_agence_can_update_offre(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre initiale',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 800,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $newDestination = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'port-gentil',
        ], $agence);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/offres/{$offre->id}", [
                'destination_id' => $newDestination->id,
                'titre' => 'Offre mise à jour',
                'type' => 'particulier',
                'prix' => 9000,
                'capacite_totale' => 1200,
                'description' => 'Nouvelle description',
                'statut' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.titre', 'Offre mise à jour')
            ->assertJsonPath('data.capacite_totale', 1200)
            ->assertJsonPath('data.capacite_disponible', 1000)
            ->assertJsonPath('data.statut', 'inactive')
            ->assertJsonPath('data.destination_id', $newDestination->id);

        $this->assertDatabaseHas('offres', [
            'id' => $offre->id,
            'destination_id' => $newDestination->id,
            'titre' => 'Offre mise à jour',
            'capacite_disponible' => 1000,
        ]);
    }

    public function test_agence_cannot_reduce_capacite_below_reserved_stock(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre stock partiel',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 700,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/offres/{$offre->id}", [
                'destination_id' => $offre->destination_id,
                'titre' => 'Offre stock partiel',
                'type' => 'particulier',
                'prix' => 8750,
                'capacite_totale' => 200,
                'statut' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacite_totale']);
    }

    public function test_agence_can_delete_offre_without_commandes(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'À supprimer',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'A',
            'arrivee' => 'B',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/offres/{$offre->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Offre supprimée avec succès.');

        $this->assertDatabaseMissing('offres', ['id' => $offre->id]);
    }

    public function test_agence_cannot_delete_offre_with_commandes(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $client = $this->createClient();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre liée',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'A',
            'arrivee' => 'B',
            'statut' => 'active',
        ]);

        Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-DEL-001',
            'quantite' => 10,
            'montant_total' => 10000,
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->deleteJson("/api/v1/agence/offres/{$offre->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['offre']);

        $this->assertDatabaseHas('offres', ['id' => $offre->id]);
    }

    public function test_agence_cannot_access_another_agences_offre(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();

        ['agence' => $otherAgence] = $this->createTestAgence([
            'nom' => 'Autre agence',
            'email' => 'autre@test.com',
            'telephone' => '0699999999',
        ]);

        $offre = $this->createOffreForAgence($otherAgence, [
            'titre' => 'Offre privée',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $this->withAgenceToken($token)
            ->getJson("/api/v1/agence/offres/{$offre->id}")
            ->assertNotFound();
    }

    public function test_agence_can_list_commandes_and_update_statut(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $client = $this->createClient();
        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre test',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-TEST-001',
            'quantite' => 10,
            'montant_sous_total' => 87500,
            'montant_commission_client' => 4375,
            'montant_total' => 91875,
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/commandes')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'CMD-TEST-001')
            ->assertJsonPath('data.0.montant', 87500)
            ->assertJsonMissingPath('data.0.montant_commission_client')
            ->assertJsonMissingPath('data.0.montant_total')
            ->assertJsonPath('data.0.offre.capacite_totale', 1000)
            ->assertJsonPath('data.0.offre.capacite_disponible', 1000);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/commandes/{$commande->id}/statut", [
                'statut' => 'confirmée',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'confirmée')
            ->assertJsonPath('data.offre.capacite_totale', 1000)
            ->assertJsonPath('data.offre.capacite_disponible', 1000);
    }

    public function test_agence_commandes_return_guest_client_from_commande_fields(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre invité',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'Paris',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => null,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-GUEST-001',
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'telephone' => '0612345678',
            'quantite' => 2,
            'montant_total' => 10000,
            'statut' => 'en_attente',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/commandes')
            ->assertOk()
            ->assertJsonPath('data.0.client.id', null)
            ->assertJsonPath('data.0.client.nom', 'Obame')
            ->assertJsonPath('data.0.client.prenom', 'Sarah')
            ->assertJsonPath('data.0.client.telephone', '0612345678');

        $this->withAgenceToken($token)
            ->getJson("/api/v1/agence/commandes/{$commande->id}")
            ->assertOk()
            ->assertJsonPath('data.client.nom', 'Obame')
            ->assertJsonPath('data.client.prenom', 'Sarah');
    }

    public function test_agence_can_list_colis_and_advance_statut(): void
    {
        ['agence' => $agence, 'user' => $user, 'token' => $token] = $this->createAuthenticatedAgence();

        $client = $this->createClient();
        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre colis',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-COLIS-001',
            'quantite' => 5,
            'montant_total' => 43750,
            'statut' => 'confirmée',
        ]);

        $colis = Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-001',
            'description' => 'Documents douane',
            'statut' => 'déposé',
        ]);

        ColisPhoto::create([
            'colis_id' => $colis->id,
            'chemin' => "colis/{$colis->id}/doc.pdf.jpg",
            'ordre' => 0,
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/colis')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'COL-001')
            ->assertJsonPath('data.0.description', 'Documents douane')
            ->assertJsonPath('data.0.photos.0.chemin', "colis/{$colis->id}/doc.pdf.jpg");

        $this->withAgenceToken($token)
            ->getJson("/api/v1/agence/colis/{$colis->id}")
            ->assertOk()
            ->assertJsonPath('data.photos.0.ordre', 0);

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/colis/{$colis->id}/statut", [
                'commentaire' => 'Expédié ce matin',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'en_transit')
            ->assertJsonPath('next_statut', 'arrivé');

        $this->assertDatabaseHas('historique_colis', [
            'colis_id' => $colis->id,
            'actor_type' => 'agence_user',
            'actor_id' => $user->id,
            'statut' => 'en_transit',
        ]);
    }

    public function test_agence_can_create_and_update_reclamation_statut(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $response = $this->withAgenceToken($token)
            ->postJson('/api/v1/agence/reclamations', [
                'nom' => 'Mba',
                'prenom' => 'Paul',
                'telephone' => '0611111111',
                'email' => 'paul@test.com',
                'objet' => 'Colis endommagé',
                'description' => 'Le colis est arrivé abîmé.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.statut', 'ouverte');

        $reclamationId = $response->json('data.id');

        $this->withAgenceToken($token)
            ->patchJson("/api/v1/agence/reclamations/{$reclamationId}/statut", [
                'statut' => 'en_cours',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'en_cours');

        $this->assertDatabaseHas('reclamations', [
            'id' => $reclamationId,
            'agence_id' => $agence->id,
        ]);
    }

    public function test_agence_can_list_paiements(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();

        $client = $this->createClient();
        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre paiement',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'depart' => 'Chine',
            'arrivee' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-PAY-001',
            'quantite' => 2,
            'montant_total' => 17500,
            'statut' => 'confirmée',
        ]);

        Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-001',
            'montant_sous_total' => 17500,
            'montant_commission_client' => 875,
            'montant_commission_agence' => 875,
            'montant_agence' => 16625,
            'montant' => 18375,
            'methode' => 'mobile_money',
            'operateur' => 'moov_money',
            'reference' => 'PAY-001',
            'bamboo_reference' => 'TXN-AGENCE-001',
            'statut' => 'validé',
        ]);

        $this->withAgenceToken($token)
            ->getJson('/api/v1/agence/paiements')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'PAY-001')
            ->assertJsonPath('data.0.montant', 16625)
            ->assertJsonPath('data.0.statut', 'validé')
            ->assertJsonPath('data.0.operateur', 'moov_money')
            ->assertJsonPath('data.0.bamboo_reference', 'TXN-AGENCE-001')
            ->assertJsonPath('data.0.commande_code', 'CMD-PAY-001')
            ->assertJsonMissingPath('data.0.montant_commission_client')
            ->assertJsonMissingPath('data.0.montant_sous_total');
    }
}
