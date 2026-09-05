<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class DestinationTest extends TestCase
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

    public function test_admin_can_view_destinations_page(): void
    {
        $this->actingAs($this->adminUser())
            ->get('/admin/destinations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/destinations/index')
                ->has('destinations.data')
                ->has('villes')
            );
    }

    public function test_admin_destinations_page_exposes_localites(): void
    {
        $this->createDestination(['depart' => 'Paris', 'arrivee' => 'Libreville']);

        $this->actingAs($this->adminUser())
            ->get('/admin/destinations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/destinations/index')
                ->where('destinations.data.0.ville_depart.ville', 'Paris')
                ->where('destinations.data.0.ville_arrivee.ville', 'Libreville')
            );
    }

    public function test_admin_can_create_destination_with_configuration(): void
    {
        $depart = $this->createVille(['pays' => 'France', 'ville' => 'Paris', 'code' => 'PAR']);
        $arrivee = $this->createVille(['pays' => 'Gabon', 'ville' => 'Libreville', 'code' => 'LBV']);

        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $arrivee->id,
                'appliquer_configuration' => true,
                'montant' => 8500,
                'commission_pourcentage' => 2.5,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('destinations', [
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
            'appliquer_configuration' => true,
            'montant' => 8500,
            'commission_pourcentage' => 2.5,
            'actif' => true,
        ]);
    }

    public function test_admin_creates_destination_without_config_nulls_amounts(): void
    {
        $depart = $this->createVille(['pays' => 'Chine', 'ville' => 'Guangzhou', 'code' => 'CAN']);
        $arrivee = $this->createVille(['pays' => 'Gabon', 'ville' => 'Libreville', 'code' => 'LBV']);

        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $arrivee->id,
                'appliquer_configuration' => false,
                'montant' => 9999,
                'commission_pourcentage' => 50,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('destinations', [
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
            'appliquer_configuration' => false,
            'montant' => null,
            'commission_pourcentage' => null,
        ]);
    }

    public function test_admin_cannot_create_duplicate_destination(): void
    {
        $depart = $this->createVille(['code' => 'DUP1']);
        $arrivee = $this->createVille(['code' => 'DUP2']);

        $this->createDestination([
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $arrivee->id,
                'appliquer_configuration' => false,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('ville_depart_id');
    }

    public function test_admin_can_update_destination(): void
    {
        $depart = $this->createVille(['pays' => 'Chine', 'ville' => 'Guangzhou', 'code' => 'CAN']);
        $arrivee = $this->createVille(['pays' => 'Gabon', 'ville' => 'Libreville', 'code' => 'LBV']);
        $nouvelleArrivee = $this->createVille(['pays' => 'Gabon', 'ville' => 'Port-Gentil', 'code' => 'POG']);

        $destination = $this->createDestination([
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/destinations/{$destination->id}", [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $nouvelleArrivee->id,
                'appliquer_configuration' => true,
                'montant' => 8750,
                'commission_pourcentage' => 10,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('destinations', [
            'id' => $destination->id,
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $nouvelleArrivee->id,
            'appliquer_configuration' => true,
            'montant' => 8750,
            'commission_pourcentage' => 10,
        ]);
    }

    public function test_admin_cannot_delete_destination_linked_to_offres(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $destination = $this->createDestination([
            'depart' => 'a',
            'arrivee' => 'b',
        ], $agence);

        $this->createOffreForAgence($agence, [
            'destination_id' => $destination->id,
            'titre' => 'Offre liée',
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/destinations/{$destination->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('destinations', ['id' => $destination->id]);
    }

    public function test_admin_cannot_create_destination_with_same_ville(): void
    {
        $localite = $this->createVille([
            'code' => 'SAME',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'ville_depart_id' => $localite->id,
                'ville_arrivee_id' => $localite->id,
                'appliquer_configuration' => false,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('ville_arrivee_id');
    }

    public function test_admin_cannot_use_inactive_ville_for_destination(): void
    {
        $depart = $this->createVille([
            'pays' => 'Chine',
            'ville' => 'Guangzhou',
            'code' => 'CAN',
            'actif' => false,
        ]);
        $arrivee = $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'ville_depart_id' => $depart->id,
                'ville_arrivee_id' => $arrivee->id,
                'appliquer_configuration' => false,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('ville_depart_id');
    }

    public function test_admin_can_delete_unused_destination(): void
    {
        $destination = $this->createDestination([
            'depart' => 'x',
            'arrivee' => 'y',
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/destinations/{$destination->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
    }
}
