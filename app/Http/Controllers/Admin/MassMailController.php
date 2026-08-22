<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendMassMailRequest;
use App\Services\MassMailService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MassMailController extends Controller
{
    public function __construct(
        private readonly MassMailService $massMail,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/notifications/masse/index', [
            'stats' => [
                'clients' => $this->massMail->countClientRecipients(),
                'agences' => $this->massMail->countAgenceRecipients(),
            ],
        ]);
    }

    public function send(SendMassMailRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $result = $this->massMail->send(
            audience: $validated['audience'],
            subject: $validated['subject'],
            message: $validated['message'],
            actionLabel: $validated['action_label'] ?? null,
            actionUrl: $validated['action_url'] ?? null,
            manualEmails: $validated['emails'] ?? null,
        );

        if ($result['queued'] === 0) {
            return back()->with('error', 'Aucun destinataire valide trouvé pour cette diffusion.');
        }

        $audienceLabel = match ($result['audience']) {
            'clients' => 'clients actifs',
            'agences' => 'gérants d\'agence',
            default => 'adresses saisies',
        };

        return back()->with(
            'success',
            "{$result['queued']} e-mail(s) mis en file d'attente pour {$audienceLabel}."
        );
    }
}
