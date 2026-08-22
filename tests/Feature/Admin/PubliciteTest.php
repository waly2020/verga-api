<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Publicite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class PubliciteTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_update_publicite_tarif(): void
    {
        $this->actingAs($this->adminUser())
            ->patch('/admin/publicites/configuration', [
                'prix_par_jour' => 5000,
                'type_frais' => 'pourcentage',
                'valeur_frais' => 3,
                'actif' => true,
                'libelle' => 'Pub VERGA',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configurations_publicite', [
            'prix_par_jour' => 5000,
            'type_frais' => 'pourcentage',
            'valeur_frais' => 3,
            'actif' => true,
        ]);
    }

    public function test_admin_can_validate_and_refuse_publicite(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Promo groupage',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(6)->toDateString(),
            'nombre_jours' => 7,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/publicites/{$publicite->id}/valider")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Publicite::STATUT_VALIDEE, $publicite->fresh()->statut);

        $other = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Refusée',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
            'nombre_jours' => 3,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/publicites/{$other->id}/refuser", [
                'motif_refus' => 'Visuel non conforme',
            ])
            ->assertRedirect();

        $this->assertSame(Publicite::STATUT_REFUSEE, $other->fresh()->statut);
        $this->assertSame('Visuel non conforme', $other->fresh()->motif_refus);
    }

    public function test_admin_can_create_internal_publicite(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser())
            ->post('/admin/publicites', [
                'proprietaire' => 'verga',
                'titre' => 'Campagne VERGA',
                'description' => 'Annonce interne',
                'lien' => 'https://verga.test',
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(6)->toDateString(),
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $publicite = Publicite::query()->where('titre', 'Campagne VERGA')->first();

        $this->assertNotNull($publicite);
        $this->assertNull($publicite->agence_id);
        $this->assertNull($publicite->client_id);
        $this->assertSame(Publicite::STATUT_PUBLIEE, $publicite->statut);
        $this->assertSame(Publicite::PAIEMENT_PAYE, $publicite->statut_paiement);
        $this->assertSame(7, $publicite->nombre_jours);
        $this->assertNotNull($publicite->image_chemin);
        Storage::disk('public')->assertExists($publicite->image_chemin);
    }

    public function test_admin_can_create_publicite_for_agence(): void
    {
        Storage::fake('public');
        ['agence' => $agence] = $this->createTestAgence();
        $offre = $this->createOffreForAgence($agence, ['titre' => 'Offre promo']);

        $this->actingAs($this->adminUser())
            ->post('/admin/publicites', [
                'proprietaire' => 'agence',
                'agence_id' => $agence->id,
                'offre_id' => $offre->id,
                'titre' => 'Pub agence',
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(2)->toDateString(),
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ])
            ->assertRedirect();

        $publicite = Publicite::query()->where('titre', 'Pub agence')->first();

        $this->assertNotNull($publicite);
        $this->assertSame($agence->id, $publicite->agence_id);
        $this->assertSame($offre->id, $publicite->offre_id);
        $this->assertSame(Publicite::STATUT_PUBLIEE, $publicite->statut);
    }

    public function test_admin_can_create_publicite_for_client(): void
    {
        Storage::fake('public');
        $clientUser = User::factory()->create(['role' => 'client']);
        $client = Client::create([
            'user_id' => $clientUser->id,
            'nom' => 'Obame',
            'prenom' => 'Sarah',
            'email' => 'sarah@test.com',
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/publicites', [
                'proprietaire' => 'client',
                'client_id' => $client->id,
                'titre' => 'Pub client',
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(1)->toDateString(),
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ])
            ->assertRedirect();

        $publicite = Publicite::query()->where('titre', 'Pub client')->first();

        $this->assertNotNull($publicite);
        $this->assertSame($client->id, $publicite->client_id);
        $this->assertNull($publicite->agence_id);
        $this->assertNull($publicite->offre_id);
        $this->assertSame(Publicite::STATUT_PUBLIEE, $publicite->statut);
        $this->assertSame(Publicite::PAIEMENT_PAYE, $publicite->statut_paiement);
    }

    public function test_admin_cannot_attach_foreign_offre_when_creating_publicite(): void
    {
        Storage::fake('public');
        ['agence' => $agence] = $this->createTestAgence();
        ['agence' => $other] = $this->createTestAgence(['email' => 'other-agence@test.com']);
        $foreign = $this->createOffreForAgence($other, ['titre' => 'Pas à elle']);

        $this->actingAs($this->adminUser())
            ->from('/admin/publicites')
            ->post('/admin/publicites', [
                'proprietaire' => 'agence',
                'agence_id' => $agence->id,
                'offre_id' => $foreign->id,
                'titre' => 'Pub invalide',
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(2)->toDateString(),
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ])
            ->assertRedirect('/admin/publicites')
            ->assertSessionHasErrors('offre_id');
    }

    public function test_admin_can_retire_published_publicite(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub active',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(6)->toDateString(),
            'nombre_jours' => 7,
            'statut' => Publicite::STATUT_PUBLIEE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/publicites/{$publicite->id}/statut", [
                'statut' => Publicite::STATUT_RETIREE,
                'motif' => 'Contenu inadapté',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Publicite::STATUT_RETIREE, $publicite->fresh()->statut);
        $this->assertSame('Contenu inadapté', $publicite->fresh()->motif_refus);
    }

    public function test_admin_can_republish_retired_publicite(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub retirée',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
            'nombre_jours' => 4,
            'statut' => Publicite::STATUT_RETIREE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/publicites/{$publicite->id}/statut", [
                'statut' => Publicite::STATUT_PUBLIEE,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Publicite::STATUT_PUBLIEE, $publicite->fresh()->statut);
    }

    public function test_admin_cannot_apply_invalid_statut_transition(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $publicite = Publicite::create([
            'agence_id' => $agence->id,
            'titre' => 'Pub refusée',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
            'nombre_jours' => 3,
            'statut' => Publicite::STATUT_REFUSEE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
            'motif_refus' => 'Déjà refusée',
        ]);

        $this->actingAs($this->adminUser())
            ->from('/admin/publicites')
            ->patch("/admin/publicites/{$publicite->id}/statut", [
                'statut' => Publicite::STATUT_RETIREE,
            ])
            ->assertRedirect('/admin/publicites')
            ->assertSessionHasErrors('statut');
    }
}
