<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class VilleTest extends TestCase
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

    public function test_admin_can_view_villes_page(): void
    {
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->actingAs($this->adminUser())
            ->get('/admin/villes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/villes/index')
                ->has('villes.data', 1)
                ->where('pays_existants.0', 'Gabon')
            );
    }

    public function test_admin_can_create_ville(): void
    {
        $this->actingAs($this->adminUser())
            ->post('/admin/villes', [
                'pays' => '  Gabon  ',
                'ville' => '  Libreville  ',
                'code' => 'lbv',
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('villes', [
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
            'actif' => true,
        ]);
    }

    public function test_admin_reuses_existing_pays_spelling(): void
    {
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/villes', [
                'pays' => 'gabon',
                'ville' => 'Port-Gentil',
                'code' => 'POG',
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('villes', [
            'pays' => 'Gabon',
            'ville' => 'Port-Gentil',
            'code' => 'POG',
        ]);
    }

    public function test_admin_cannot_create_duplicate_code(): void
    {
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/villes', [
                'pays' => 'France',
                'ville' => 'Paris',
                'code' => 'LBV',
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('code');
    }

    public function test_admin_cannot_create_duplicate_ville_for_same_pays(): void
    {
        $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->actingAs($this->adminUser())
            ->post('/admin/villes', [
                'pays' => 'Gabon',
                'ville' => 'Libreville',
                'code' => 'LBV2',
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('ville');
    }

    public function test_admin_can_update_ville(): void
    {
        $ville = $this->createVille([
            'pays' => 'Gabon',
            'ville' => 'Libreville',
            'code' => 'LBV',
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/villes/{$ville->id}", [
                'pays' => 'Gabon',
                'ville' => 'Port-Gentil',
                'code' => 'POG',
                'actif' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('villes', [
            'id' => $ville->id,
            'ville' => 'Port-Gentil',
            'code' => 'POG',
        ]);
    }

    public function test_admin_can_delete_unused_ville(): void
    {
        $ville = $this->createVille([
            'code' => 'DEL',
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/villes/{$ville->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('villes', ['id' => $ville->id]);
    }

    public function test_admin_cannot_delete_ville_used_by_destination(): void
    {
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

        $this->createDestination([
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/villes/{$depart->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('villes', ['id' => $depart->id]);
    }
}
