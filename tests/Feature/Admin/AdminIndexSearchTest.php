<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_search_agences_clients_commandes_and_colis(): void
    {
        $this->actingAsAdmin();

        $agenceUser = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $agenceUser->id,
            'nom' => 'Transit Libreville',
            'email' => 'libreville@transit.test',
            'telephone' => '0611111111',
            'ville' => 'Libreville',
            'statut' => 'actif',
        ]);

        $clientUser = User::factory()->create(['role' => 'client']);
        $client = Client::create([
            'user_id' => $clientUser->id,
            'nom' => 'Obame',
            'prenom' => 'Paul',
            'email' => 'paul.obame@test.com',
            'telephone' => '0622222222',
            'statut' => 'actif',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Groupage',
            'type' => 'particulier',
            'prix' => 5000,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'origine' => 'Paris',
            'destination' => 'Libreville',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'client_id' => $client->id,
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-SEARCH-001',
            'quantite' => 2,
            'montant_total' => 10000,
            'statut' => 'confirmée',
        ]);

        Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-SEARCH-001',
            'description' => 'Cartons électronique',
            'statut' => 'déposé',
        ]);

        $this->get('/admin/agences?search=Libreville')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/agences/index')
                ->where('filters.search', 'Libreville')
                ->has('agences.data', 1));

        $this->get('/admin/clients?search=Obame')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/clients/index')
                ->where('filters.search', 'Obame')
                ->has('clients.data', 1));

        $this->get('/admin/commandes?search=CMD-SEARCH')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/commandes/index')
                ->where('filters.search', 'CMD-SEARCH')
                ->has('commandes.data', 1));

        $this->get('/admin/commandes?search=Paul')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('commandes.data', 1));

        $this->get('/admin/colis?search=électronique')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/colis/index')
                ->where('filters.search', 'électronique')
                ->has('colis.data', 1));
    }
}
