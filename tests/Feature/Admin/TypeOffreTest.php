<?php

namespace Tests\Feature\Admin;

use App\Models\Agence;
use App\Models\Offre;
use App\Models\TypeOffre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypeOffreTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_view_types_offres_page(): void
    {
        $this->actingAs($this->adminUser())
            ->get('/admin/types-offres')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/types-offres/index')
                ->has('types_offres.data', 3)
            );
    }

    public function test_admin_can_create_type_offre(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post('/admin/types-offres', [
                'slug' => 'palette',
                'nom' => 'Palette',
                'description' => 'Transport par palette',
                'unite' => 'palette',
                'unite_label' => 'par palette',
                'quantite_entier' => true,
                'quantite_min' => 1,
                'actif' => true,
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('types_offres', [
            'slug' => 'palette',
            'nom' => 'Palette',
            'quantite_entier' => true,
            'actif' => true,
        ]);
    }

    public function test_admin_can_update_type_offre(): void
    {
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();

        $response = $this->actingAs($this->adminUser())
            ->patch("/admin/types-offres/{$type->id}", [
                'nom' => 'Particulier (kg)',
                'description' => 'Mis à jour',
                'unite' => 'kg',
                'unite_label' => 'au kilogramme',
                'quantite_entier' => false,
                'quantite_min' => 0.5,
                'actif' => true,
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('types_offres', [
            'id' => $type->id,
            'slug' => 'particulier',
            'nom' => 'Particulier (kg)',
            'quantite_min' => 0.5,
        ]);
    }

    public function test_admin_cannot_delete_type_offre_linked_to_offres(): void
    {
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();
        $user = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $user->id,
            'nom' => 'Test',
            'email' => 'test@agence.com',
            'telephone' => '0600000000',
            'statut' => 'actif',
        ]);

        Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre test',
            'type' => 'particulier',
            'type_offre_id' => $type->id,
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'A',
            'destination' => 'B',
            'statut' => 'active',
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/types-offres/{$type->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('types_offres', ['id' => $type->id]);
    }

    public function test_admin_can_delete_unused_type_offre(): void
    {
        $type = TypeOffre::create([
            'slug' => 'test_delete',
            'nom' => 'À supprimer',
            'unite' => 'unit',
            'unite_label' => 'par unité',
            'quantite_entier' => false,
            'quantite_min' => 1,
            'actif' => true,
        ]);

        $this->actingAs($this->adminUser())
            ->delete("/admin/types-offres/{$type->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('types_offres', ['id' => $type->id]);
    }

    public function test_guest_cannot_access_types_offres_admin(): void
    {
        $this->get('/admin/types-offres')->assertRedirect('/login');
    }
}
