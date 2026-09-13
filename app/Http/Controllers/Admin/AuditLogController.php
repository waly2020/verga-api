<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAuditLogsRequest;
use App\Services\Audit\AuditLogReader;
use App\Support\Audit\AuditAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(ListAuditLogsRequest $request, AuditLogReader $reader): Response
    {
        $filters = $request->validated();
        $filters['date'] ??= now((string) config('verga.audit.timezone'))->toDateString();

        return Inertia::render('admin/logs/index', [
            'entries' => $reader->paginate($filters),
            'filters' => [
                'date' => $filters['date'],
                'action' => $filters['action'] ?? null,
                'search' => $filters['search'] ?? null,
            ],
            'jours' => $reader->days(),
            'actions' => AuditAction::options(),
        ]);
    }

    public function download(Request $request, string $date, AuditLogReader $reader): StreamedResponse
    {
        abort_unless($request->user()?->isAdmin() === true, 403);
        abort_unless((bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);
        abort_unless($reader->exists($date), 404);

        return response()->streamDownload(
            function () use ($reader, $date): void {
                $handle = fopen($reader->absolutePath($date), 'rb');

                if ($handle === false) {
                    return;
                }

                try {
                    fpassthru($handle);
                } finally {
                    fclose($handle);
                }
            },
            "audit-{$date}.json",
            ['Content-Type' => 'application/json'],
        );
    }
}
