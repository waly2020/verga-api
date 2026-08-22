<?php

namespace Tests\Feature\Api\Client;

use App\Models\Publicite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PubliciteTest extends ClientApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_client_can_create_publicite_without_offre(): void
    {
        ['token' => $token] = $this->createAuthenticatedClient();

        $this->withClientToken($token)
            ->post('/api/v1/client/publicites', [
                'titre' => 'Produit client',
                'date_debut' => '2026-09-01',
                'date_fin' => '2026-09-03',
                'image' => UploadedFile::fake()->image('prod.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.titre', 'Produit client')
            ->assertJsonPath('data.offre_id', null)
            ->assertJsonPath('data.statut', 'en_attente');
    }

    public function test_public_catalog_lists_only_published_current_ads(): void
    {
        ['client' => $client] = $this->createAuthenticatedClient();

        Publicite::create([
            'client_id' => $client->id,
            'titre' => 'Visible',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
            'nombre_jours' => 4,
            'statut' => Publicite::STATUT_PUBLIEE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        Publicite::create([
            'client_id' => $client->id,
            'titre' => 'Pas encore',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
            'nombre_jours' => 4,
            'statut' => Publicite::STATUT_VALIDEE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->getJson('/api/v1/publicites')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Visible');
    }
}
