<?php

namespace Tests\Feature\Admin;

use App\Models\ConfigurationCommission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationCommissionTest extends TestCase
{
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

    public function test_admin_can_view_commissions_configuration_page(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/admin/commissions');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/commissions/index')
                ->has('client', fn ($prop) => $prop
                    ->where('destinataire', 'client')
                    ->etc()
                )
                ->has('agence', fn ($prop) => $prop
                    ->where('destinataire', 'agence')
                    ->etc()
                )
            );

        $this->assertDatabaseHas('configurations_commission', [
            'destinataire' => 'client',
        ]);
        $this->assertDatabaseHas('configurations_commission', [
            'destinataire' => 'agence',
        ]);
    }

    public function test_admin_can_update_client_commission_configuration(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->patch('/admin/commissions/client', [
                'type' => 'pourcentage',
                'valeur' => 5,
                'actif' => true,
                'libelle' => 'Commission clients 5 %',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configurations_commission', [
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 5,
            'actif' => true,
            'libelle' => 'Commission clients 5 %',
        ]);
    }

    public function test_admin_can_update_agence_commission_as_fixed_amount(): void
    {
        ConfigurationCommission::create([
            'destinataire' => 'agence',
            'type' => 'pourcentage',
            'valeur' => 0,
            'actif' => false,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->patch('/admin/commissions/agence', [
                'type' => 'fixe',
                'valeur' => 250,
                'actif' => true,
                'libelle' => 'Frais agence',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configurations_commission', [
            'destinataire' => 'agence',
            'type' => 'fixe',
            'valeur' => 250,
            'actif' => true,
        ]);
    }

    public function test_percentage_cannot_exceed_one_hundred(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->patch('/admin/commissions/client', [
                'type' => 'pourcentage',
                'valeur' => 150,
                'actif' => true,
            ]);

        $response->assertSessionHasErrors(['valeur']);
    }

    public function test_guest_cannot_access_commissions_configuration(): void
    {
        $this->get('/admin/commissions')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_commissions_configuration(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/commissions')
            ->assertForbidden();
    }

    public function test_admin_can_save_client_grille_with_nullable_libelle(): void
    {
        $this->actingAs($this->adminUser())
            ->patch('/admin/commissions/client', [
                'type' => 'grille',
                'actif' => true,
                'libelle' => 'Frais de service VERGA',
                'paliers' => [
                    [
                        'montant_min' => 0,
                        'montant_max' => 9999,
                        'frais' => 1500,
                        'libelle' => null,
                    ],
                    [
                        'montant_min' => 10000,
                        'montant_max' => null,
                        'frais' => 2500,
                        'libelle' => 'Frais Dossier',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configurations_commission', [
            'destinataire' => 'client',
            'type' => 'grille',
            'actif' => true,
        ]);
        $this->assertDatabaseHas('commission_paliers', [
            'montant_min' => 0,
            'montant_max' => 9999,
            'frais' => 1500,
            'libelle' => null,
        ]);
        $this->assertDatabaseHas('commission_paliers', [
            'montant_min' => 10000,
            'frais' => 2500,
            'libelle' => 'Frais Dossier',
        ]);
    }

    public function test_agence_cannot_use_grille(): void
    {
        $this->actingAs($this->adminUser())
            ->patch('/admin/commissions/agence', [
                'type' => 'grille',
                'actif' => true,
                'paliers' => [
                    ['montant_min' => 0, 'montant_max' => null, 'frais' => 1000],
                ],
            ])
            ->assertSessionHasErrors('type');
    }
}
