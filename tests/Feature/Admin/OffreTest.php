<?php

namespace Tests\Feature\Admin;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Logo;
use App\Models\Offre;
use App\Models\TypeOffre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class OffreTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{agence: Agence}
     */
    private function createAgence(): array
    {
        ['agence' => $agence] = $this->createTestAgence([
            'nom' => 'Transit Test',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
        ]);

        return compact('agence');
    }

    public function test_admin_can_view_offres_page(): void
    {
        $this->actingAs($this->adminUser())
            ->get('/admin/offres')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/offres/index')
                ->has('offres.data')
                ->has('agences')
                ->has('types_offres')
                ->has('destinations')
            );
    }

    public function test_admin_offres_page_includes_agence_logo(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();

        Logo::create([
            'agence_id' => $agence->id,
            'chemin' => "logos/{$agence->id}/logo.png",
            'nom_original' => 'logo.png',
        ]);

        $this->createOffreForAgence($agence, [
            'type_offre_id' => $type->id,
            'titre' => 'Offre avec logo',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'depart' => 'Libreville',
            'arrivee' => 'Paris',
            'statut' => 'active',
        ]);

        $this->actingAs($this->adminUser())
            ->get('/admin/offres')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/offres/index')
                ->where('offres.data.0.agence.nom', 'Transit Test')
                ->where('offres.data.0.agence.logo.chemin', "logos/{$agence->id}/logo.png")
                ->where('offres.data.0.agence.logo.nom_original', 'logo.png')
            );
    }

    public function test_admin_can_create_offre(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();
        $destination = $this->createDestination([
            'depart' => 'libreville',
            'arrivee' => 'paris',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/offres', [
                'agence_id' => $agence->id,
                'destination_id' => $destination->id,
                'titre' => 'Nouvelle offre admin',
                'type_offre_id' => $type->id,
                'prix' => 5000,
                'capacite_totale' => 1000,
                'date_depart' => '2026-07-20',
                'date_depot_colis' => '2026-07-19',
                'statut' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'destination_id' => $destination->id,
            'titre' => 'Nouvelle offre admin',
            'type' => 'particulier',
            'capacite_illimitee' => false,
        ]);

        $this->assertTrue(
            $destination->agences()->where('agences.id', $agence->id)->exists()
        );

        $offre = Offre::where('titre', 'Nouvelle offre admin')->firstOrFail();
        $this->assertSame('2026-07-20', $offre->date_depart?->toDateString());
        $this->assertSame('2026-07-19', $offre->date_depot_colis?->toDateString());
    }

    public function test_admin_can_create_offre_capacite_illimitee(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();
        $destination = $this->createDestination([
            'depart' => 'libreville',
            'arrivee' => 'port-gentil',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/offres', [
                'agence_id' => $agence->id,
                'destination_id' => $destination->id,
                'titre' => 'Offre illimitée',
                'type_offre_id' => $type->id,
                'prix' => 2000,
                'capacite_illimitee' => true,
                'statut' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'titre' => 'Offre illimitée',
            'capacite_illimitee' => true,
            'capacite_totale' => null,
            'capacite_disponible' => null,
        ]);
    }

    public function test_admin_forces_prix_when_destination_has_configuration(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();
        $destination = $this->createDestination([
            'depart' => 'chine',
            'arrivee' => 'libreville',
            'appliquer_configuration' => true,
            'montant' => 8750,
            'commission_pourcentage' => 10,
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/offres', [
                'agence_id' => $agence->id,
                'destination_id' => $destination->id,
                'titre' => 'Offre config forcée',
                'type_offre_id' => $type->id,
                'prix' => 1,
                'capacite_totale' => 100,
                'statut' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offres', [
            'titre' => 'Offre config forcée',
            'prix' => 8750,
        ]);
    }

    public function test_admin_cannot_create_offre_with_inactive_destination(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();
        $destination = $this->createDestination([
            'depart' => 'inactive',
            'arrivee' => 'ville',
            'actif' => false,
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/offres', [
                'agence_id' => $agence->id,
                'destination_id' => $destination->id,
                'titre' => 'Offre inactive',
                'type_offre_id' => $type->id,
                'prix' => 1000,
                'capacite_totale' => 10,
                'statut' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('destination_id');
    }

    public function test_admin_can_update_offre(): void
    {
        ['agence' => $agence] = $this->createAgence();

        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Offre initiale',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 1000,
            'capacite_disponible' => 800,
            'depart' => 'Libreville',
            'arrivee' => 'Paris',
            'statut' => 'active',
        ]);

        $newDestination = $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'port-gentil',
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/offres/{$offre->id}", [
                'agence_id' => $agence->id,
                'destination_id' => $newDestination->id,
                'titre' => 'Offre modifiée',
                'type' => 'particulier',
                'prix' => 6000,
                'capacite_totale' => 1200,
                'statut' => 'inactive',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offres', [
            'id' => $offre->id,
            'destination_id' => $newDestination->id,
            'titre' => 'Offre modifiée',
            'capacite_disponible' => 1000,
            'statut' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_offre_without_commandes(): void
    {
        ['agence' => $agence] = $this->createAgence();

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

        $this->actingAs($this->adminUser())
            ->delete("/admin/offres/{$offre->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('offres', ['id' => $offre->id]);
    }

    public function test_admin_cannot_delete_offre_with_commandes(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $clientUser = User::factory()->create(['role' => 'client']);
        $client = Client::create([
            'user_id' => $clientUser->id,
            'nom' => 'Test',
            'prenom' => 'Client',
            'email' => 'client@test.com',
            'telephone' => '0622222222',
        ]);

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
            'code' => 'CMD-ADM-001',
            'quantite' => 10,
            'montant_total' => 10000,
            'statut' => 'en_attente',
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/offres/{$offre->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('offres', ['id' => $offre->id]);
    }
}
