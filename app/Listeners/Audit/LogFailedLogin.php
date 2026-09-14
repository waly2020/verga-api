<?php

namespace App\Listeners\Audit;

use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->audit->record(
            AuditAction::AuthAdminLoginFailed,
            [
                'guard' => $event->guard,
                'email' => $email,
            ],
            actor: [
                'type' => 'inconnu',
                'id' => $event->user?->getAuthIdentifier(),
                'name' => data_get($event->user, 'name'),
                'email' => is_string($email) ? $email : data_get($event->user, 'email'),
                'role' => data_get($event->user, 'role'),
            ],
        );
    }
}
