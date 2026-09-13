<?php

namespace App\Listeners\Audit;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User || ! $event->user->isAdmin()) {
            return;
        }

        $this->audit->record(
            AuditAction::AuthAdminLogout,
            [
                'guard' => $event->guard,
            ],
            actor: $this->audit->actorFromUser($event->user),
        );
    }
}
