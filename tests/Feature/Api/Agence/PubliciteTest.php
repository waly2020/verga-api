<?php

namespace Tests\Feature\Api\Agence;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use App\Models\ConfigurationPublicite;
use App\Models\Publicite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class PubliciteTest extends AgenceApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        ConfigurationPublicite::create([
            'prix_par_jour' => 1000,
            'type_frais' => 'fixe',
            'valeur_frais' => 500,
            'actif' => true,
            'libelle' => 'Tarif test',
        ]);
    }

    public function test_agence_can_create_update_and_resubmit_publicite(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $offre = $this->createOffreForAgence($agence, ['titre' => 'Offre A']);

        $created = $this->withAgenceToken($token)
            ->post('/api/v1/agence/publicites', [
                'titre' => 'Pub été',
                'description' => 'Promo',
                'lien' => 'https://example.test/promo',
                'date_debut' => '2026-08-20',
                'date_fin' => '2026-08-26',
                'offre_id' => $offre->id,
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ], ['Accept' => 'application/json']);

        $created->assertCreated()
            ->assertJsonPath('data.titre', 'Pub été')
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.nombre_jours', 7)
            ->assertJsonPath('data.offre_id', $offre->id);

        $id = $created->json('data.id');

        $this->withAgenceToken($token)
            ->patch("/api/v1/agence/publicites/{$id}", [
                'titre' => 'Pub été v2',
                'date_debut' => '2026-08-20',
                'date_fin' => '2026-08-26',
            ])
            ->assertOk()
            ->assertJsonPath('data.titre', 'Pub été v2');

        $publicite = Publicite::query()->findOrFail($id);
        $publicite->update([
            'statut' => Publicite::STATUT_REFUSEE,
            'motif_refus' => 'Image floue',
        ]);

        $this->withAgenceToken($token)
            ->postJson("/api/v1/agence/publicites/{$id}/resoumettre")
            ->assertOk()
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.motif_refus', null);
    }

    public function test_agence_cannot_attach_foreign_offre(): void
    {
        ['token' => $token] = $this->createAuthenticatedAgence();
        ['agence' => $other] = $this->createAuthenticatedAgence(['email' => 'other@test.com']);
        $foreign = $this->createOffreForAgence($other, ['titre' => 'Pas à moi']);

        $this->withAgenceToken($token)
            ->post('/api/v1/agence/publicites', [
                'titre' => 'Pub volée',
                'date_debut' => '2026-08-20',
                'date_fin' => '2026-08-21',
                'offre_id' => $foreign->id,
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['offre_id']);
    }

    public function test_agence_can_pay_validated_publicite(): void
    {
        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            RedirectPaymentRequest::class => MockResponse::make([
                'redirect_url' => 'https://bamboo.test/pay/pub',
            ], 200),
        ]));
        $this->app->instance(BambooPayConnector::class, $connector);

        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'À payer',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
            'nombre_jours' => 3,
            'statut' => Publicite::STATUT_VALIDEE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->withAgenceToken($token)
            ->postJson("/api/v1/agence/publicites/{$publicite->id}/paiement")
            ->assertCreated()
            ->assertJsonPath('data.redirect_url', 'https://bamboo.test/pay/pub')
            ->assertJsonPath('data.montant_sous_total', 3000)
            ->assertJsonPath('data.montant_frais', 500)
            ->assertJsonPath('data.montant_total', 3500);

        $this->assertSame(Publicite::PAIEMENT_EN_ATTENTE, $publicite->fresh()->statut_paiement);
    }

    public function test_cannot_pay_before_admin_validation(): void
    {
        ['agence' => $agence, 'token' => $token] = $this->createAuthenticatedAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Trop tôt',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDay()->toDateString(),
            'nombre_jours' => 2,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->withAgenceToken($token)
            ->postJson("/api/v1/agence/publicites/{$publicite->id}/paiement")
            ->assertUnprocessable();
    }
}
