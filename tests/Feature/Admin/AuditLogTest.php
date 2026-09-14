<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsAuditLogs;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use AssertsAuditLogs;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_peut_consulter_les_logs_du_jour(): void
    {
        $this->actingAsAdmin();

        app(AuditLogService::class)->record(AuditAction::ReversementEffectue, [
            'montant' => 15000,
        ]);

        $this->get(route('admin.logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/logs/index')
                ->has('entries.data', 1)
                ->where('entries.data.0.action', AuditAction::ReversementEffectue)
                ->has('actions')
                ->has('jours'));
    }

    public function test_invite_ne_peut_pas_consulter_les_logs(): void
    {
        $this->get(route('admin.logs.index'))->assertRedirect();
    }

    public function test_client_ne_peut_pas_consulter_les_logs(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($client)
            ->get(route('admin.logs.index'))
            ->assertForbidden();
    }

    public function test_aucune_route_de_suppression_des_logs(): void
    {
        $this->actingAsAdmin();

        $this->delete('/admin/logs/'.now()->toDateString())->assertNotFound();
    }

    public function test_modification_commission_est_journalisee(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patch(route('admin.commissions.update', 'client'), [
            'type' => 'pourcentage',
            'valeur' => 5,
            'actif' => true,
            'libelle' => 'Commission test',
        ])->assertRedirect();

        $entries = $this->auditEntries(AuditAction::CommissionUpdated);

        $this->assertNotEmpty($entries);
        $this->assertSame('client', $entries[0]['context']['destinataire']);
        $this->assertSame($admin->id, $entries[0]['actor']['id']);
        $this->assertSame('admin', $entries[0]['actor']['type']);
    }

    public function test_echec_connexion_admin_est_journalise(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@verga.test',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@verga.test',
            'password' => 'mauvais-mot-de-passe',
        ]);

        $entries = $this->auditEntries(AuditAction::AuthAdminLoginFailed);

        $this->assertNotEmpty($entries);
        $this->assertSame('admin@verga.test', $entries[0]['context']['email']);
        $this->assertArrayNotHasKey('password', $entries[0]['context']);
    }
}
