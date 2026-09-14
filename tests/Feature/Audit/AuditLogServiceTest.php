<?php

namespace Tests\Feature\Audit;

use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsAuditLogs;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use AssertsAuditLogs;
    use RefreshDatabase;

    public function test_ecrit_une_ligne_json_et_redacte_les_secrets(): void
    {
        app(AuditLogService::class)->record(AuditAction::CommissionUpdated, [
            'destinataire' => 'client',
            'password' => 'secret-admin',
            'nested' => ['token' => 'abc123', 'montant' => 1500],
        ], actor: [
            'type' => 'admin',
            'id' => 1,
            'name' => 'Admin VERGA',
            'email' => 'admin@verga.test',
            'role' => 'admin',
        ]);

        $entries = $this->auditEntries(AuditAction::CommissionUpdated);

        $this->assertCount(1, $entries);
        $this->assertSame('critical', $entries[0]['level']);
        $this->assertSame('Admin VERGA', $entries[0]['actor']['name']);
        $this->assertSame('client', $entries[0]['context']['destinataire']);
        $this->assertSame('[redacted]', $entries[0]['context']['password']);
        $this->assertSame('[redacted]', $entries[0]['context']['nested']['token']);
        $this->assertSame(1500, $entries[0]['context']['nested']['montant']);
    }
}
