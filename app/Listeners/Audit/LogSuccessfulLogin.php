<?php

namespace App\Listeners\Audit;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->record(
            AuditAction::AuthAdminLoginSuccess,
            [
                'guard' => $event->guard,
            ],
            actor: $this->audit->actorFromUser($event->user),
        );
    }
}
