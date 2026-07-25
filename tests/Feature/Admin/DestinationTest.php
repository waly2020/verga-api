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
            );
    }

    public function test_admin_can_create_destination_with_configuration(): void
    {
        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'depart' => 'France',
                'arrivee' => 'Gabon',
                'appliquer_configuration' => true,
                'montant' => 8500,
                'commission_pourcentage' => 2.5,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('destinations', [
            'depart' => 'france',
            'arrivee' => 'gabon',
            'appliquer_configuration' => true,
            'montant' => 8500,
            'commission_pourcentage' => 2.5,
            'actif' => true,
        ]);
    }

    public function test_admin_creates_destination_without_config_nulls_amounts(): void
    {
        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'depart' => 'Chine',
                'arrivee' => 'Libreville',
                'appliquer_configuration' => false,
                'montant' => 9999,
                'commission_pourcentage' => 50,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('destinations', [
            'depart' => 'chine',
            'arrivee' => 'libreville',
            'appliquer_configuration' => false,
            'montant' => null,
            'commission_pourcentage' => null,
        ]);
    }

    public function test_admin_cannot_create_duplicate_destination(): void
    {
        $this->createDestination([
            'depart' => 'france',
            'arrivee' => 'gabon',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/destinations', [
                'depart' => 'France',
                'arrivee' => 'Gabon',
                'appliquer_configuration' => false,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('depart');
    }

    public function test_admin_can_update_destination(): void
    {
        $destination = $this->createDestination([
            'depart' => 'chine',
            'arrivee' => 'libreville',
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/destinations/{$destination->id}", [
                'depart' => 'Chine',
                'arrivee' => 'Port-Gentil',
                'appliquer_configuration' => true,
                'montant' => 8750,
                'commission_pourcentage' => 10,
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('destinations', [
            'id' => $destination->id,
            'depart' => 'chine',
            'arrivee' => 'port-gentil',
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
