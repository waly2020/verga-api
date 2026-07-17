<?php

namespace Tests\Feature\Admin;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Commande;
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
            );
    }

    public function test_admin_can_create_offre(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();

        $this->actingAs($this->adminUser())
            ->post('/admin/offres', [
                'agence_id' => $agence->id,
                'titre' => 'Nouvelle offre admin',
                'type_offre_id' => $type->id,
                'prix' => 5000,
                'capacite_totale' => 1000,
                'origine' => 'Libreville',
                'destination' => 'Paris',
                'statut' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offres', [
            'agence_id' => $agence->id,
            'titre' => 'Nouvelle offre admin',
            'type' => 'particulier',
            'capacite_illimitee' => false,
        ]);
    }

    public function test_admin_can_create_offre_capacite_illimitee(): void
    {
        ['agence' => $agence] = $this->createAgence();
        $type = TypeOffre::query()->where('slug', 'particulier')->firstOrFail();

        $this->actingAs($this->adminUser())
            ->post('/admin/offres', [
                'agence_id' => $agence->id,
                'titre' => 'Offre illimitée',
                'type_offre_id' => $type->id,
                'prix' => 2000,
                'capacite_illimitee' => true,
                'origine' => 'Libreville',
                'destination' => 'Port-Gentil',
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

    public function test_admin_can_update_offre(): void
    {
        ['agence' => $agence] = $this->createAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre initiale',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 1000,
            'capacite_disponible' => 800,
            'origine' => 'Libreville',
            'destination' => 'Paris',
            'statut' => 'active',
        ]);

        $this->actingAs($this->adminUser())
            ->patch("/admin/offres/{$offre->id}", [
                'agence_id' => $agence->id,
                'titre' => 'Offre modifiée',
                'type' => 'particulier',
                'prix' => 6000,
                'capacite_totale' => 1200,
                'origine' => 'France',
                'destination' => 'Port-Gentil',
                'statut' => 'inactive',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offres', [
            'id' => $offre->id,
            'titre' => 'Offre modifiée',
            'capacite_disponible' => 1000,
            'statut' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_offre_without_commandes(): void
    {
        ['agence' => $agence] = $this->createAgence();

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'À supprimer',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'A',
            'destination' => 'B',
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

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Offre liée',
            'type' => 'particulier',
            'prix' => 1000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'A',
            'destination' => 'B',
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
