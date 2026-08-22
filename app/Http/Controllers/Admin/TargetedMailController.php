<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTargetedMailRequest;
use App\Models\Agence;
use App\Models\Client;
use App\Services\MassMailService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TargetedMailController extends Controller
{
    public function __construct(
        private readonly MassMailService $massMail,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/notifications/cible/index', [
            'clients' => Client::query()
                ->where('statut', 'actif')
                ->with('user:id,email')
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get(['id', 'nom', 'prenom', 'email', 'user_id'])
                ->map(fn (Client $client) => [
                    'id' => $client->id,
                    'label' => trim("{$client->prenom} {$client->nom}"),
                    'email' => $client->email ?: $client->user?->email,
                ])
                ->filter(fn (array $row) => is_string($row['email']) && $row['email'] !== '')
                ->values(),
            'agences' => Agence::query()
                ->where('statut', 'actif')
                ->with('proprietaire:id,agence_id,email,name')
                ->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->map(function (Agence $agence): ?array {
                    $email = $agence->proprietaire?->email ?: $agence->email;

                    if (! is_string($email) || $email === '') {
                        return null;
                    }

                    return [
                        'id' => $agence->id,
                        'label' => $agence->nom,
                        'email' => $email,
                    ];
                })
                ->filter()
                ->values(),
        ]);
    }

    public function send(SendTargetedMailRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $sent = $this->massMail->sendTargeted(
            recipientType: $validated['recipient_type'],
            clientId: $validated['client_id'] ?? null,
            agenceId: $validated['agence_id'] ?? null,
            email: $validated['email'] ?? null,
            subject: $validated['subject'],
            message: $validated['message'],
            actionLabel: $validated['action_label'] ?? null,
            actionUrl: $validated['action_url'] ?? null,
        );

        if (! $sent) {
            return back()->with('error', 'Impossible d\'envoyer l\'e-mail : destinataire introuvable ou adresse invalide.');
        }

        return back()->with('success', 'E-mail mis en file d\'attente pour le destinataire sélectionné.');
    }
}
