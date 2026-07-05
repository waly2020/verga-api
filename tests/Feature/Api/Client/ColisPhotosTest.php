<?php

namespace Tests\Feature\Api\Client;

use App\Models\Agence;
use App\Models\Colis;
use App\Models\ColisPhoto;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\User;

class ColisPhotosTest extends ClientApiTestCase
{
    public function test_client_colis_list_and_show_include_photos(): void
    {
        ['client' => $client, 'token' => $token] = $this->createAuthenticatedClient();

        $agenceUser = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $agenceUser->id,
            'nom' => 'Transit Photos',
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre test',
            'type' => 'particulier',
            'prix' => 8750,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Chine',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-PHOTO-001',
            'quantite' => 2,
            'montant_total' => 17500,
            'statut' => 'confirmée',
        ]);

        $colis = Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-PHOTO-001',
            'description' => 'Cartons vêtements',
            'statut' => 'déposé',
        ]);

        ColisPhoto::create([
            'colis_id' => $colis->id,
            'chemin' => "colis/{$colis->id}/photo-1.jpg",
            'ordre' => 0,
        ]);

        $this->withClientToken($token)
            ->getJson('/api/v1/client/colis')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'COL-PHOTO-001')
            ->assertJsonPath('data.0.description', 'Cartons vêtements')
            ->assertJsonPath('data.0.photos.0.chemin', "colis/{$colis->id}/photo-1.jpg")
            ->assertJsonPath('data.0.photos.0.ordre', 0)
            ->assertJsonStructure([
                'data' => [
                    [
                        'photos' => [
                            ['id', 'chemin', 'url', 'ordre'],
                        ],
                    ],
                ],
            ]);

        $this->withClientToken($token)
            ->getJson("/api/v1/client/colis/{$colis->id}")
            ->assertOk()
            ->assertJsonPath('data.photos.0.chemin', "colis/{$colis->id}/photo-1.jpg");

        $this->withClientToken($token)
            ->getJson("/api/v1/client/commandes/{$commande->id}")
            ->assertOk()
            ->assertJsonPath('data.colis.0.photos.0.chemin', "colis/{$colis->id}/photo-1.jpg");
    }
}
