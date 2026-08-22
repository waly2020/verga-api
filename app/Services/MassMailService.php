<?php

namespace App\Services;

use App\Mail\Admin\MassNotificationMail;
use App\Models\Agence;
use App\Models\AgenceUser;
use App\Models\Client;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MassMailService
{
    public function __construct(
        private readonly MailService $mail,
    ) {}

    public function countClientRecipients(): int
    {
        return $this->clientEmails()->count();
    }

    public function countAgenceRecipients(): int
    {
        return $this->agenceEmails()->count();
    }

    /**
     * @return array{queued: int, audience: string}
     */
    public function send(
        string $audience,
        string $subject,
        string $message,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        ?string $manualEmails = null,
    ): array {
        $recipients = match ($audience) {
            'clients' => $this->clientEmails(),
            'agences' => $this->agenceEmails(),
            'manual' => $this->parseManualEmails($manualEmails ?? ''),
            default => throw new InvalidArgumentException('Audience de diffusion non reconnue.'),
        };

        if ($recipients->isEmpty()) {
            return [
                'queued' => 0,
                'audience' => $audience,
            ];
        }

        $lines = $this->messageLines($message);
        $action = $this->buildAction($actionLabel, $actionUrl);

        foreach ($recipients as $email) {
            $this->mail->queue(
                new MassNotificationMail($subject, $lines, $action),
                $email,
            );
        }

        return [
            'queued' => $recipients->count(),
            'audience' => $audience,
        ];
    }

    public function sendTargeted(
        string $recipientType,
        ?string $clientId,
        ?string $agenceId,
        ?string $email,
        string $subject,
        string $message,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
    ): bool {
        $resolvedEmail = match ($recipientType) {
            'client' => $this->emailForClient($clientId),
            'agence' => $this->emailForAgence($agenceId),
            'email' => $this->normalizeEmail($email),
            default => null,
        };

        if (! $resolvedEmail) {
            return false;
        }

        $this->queueMail($resolvedEmail, $subject, $message, $actionLabel, $actionUrl);

        return true;
    }

    private function queueMail(
        string $email,
        string $subject,
        string $message,
        ?string $actionLabel,
        ?string $actionUrl,
    ): void {
        $this->mail->queue(
            new MassNotificationMail($subject, $this->messageLines($message), $this->buildAction($actionLabel, $actionUrl)),
            $email,
        );
    }

    private function emailForClient(?string $clientId): ?string
    {
        if (! $clientId) {
            return null;
        }

        $client = Client::query()
            ->whereKey($clientId)
            ->where('statut', 'actif')
            ->with('user:id,email')
            ->first(['id', 'email', 'user_id']);

        if (! $client) {
            return null;
        }

        return $this->normalizeEmail($client->email ?: $client->user?->email);
    }

    private function emailForAgence(?string $agenceId): ?string
    {
        if (! $agenceId) {
            return null;
        }

        $agence = Agence::query()
            ->whereKey($agenceId)
            ->where('statut', 'actif')
            ->with('proprietaire:id,agence_id,email')
            ->first(['id', 'email']);

        if (! $agence) {
            return null;
        }

        return $this->normalizeEmail($agence->proprietaire?->email ?: $agence->email);
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! is_string($email) || $email === '') {
            return null;
        }

        $normalized = strtolower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    /**
     * @return Collection<int, string>
     */
    private function clientEmails(): Collection
    {
        return Client::query()
            ->where('statut', 'actif')
            ->with('user:id,email')
            ->get(['id', 'email', 'user_id'])
            ->map(function (Client $client): ?string {
                $email = $client->email ?: $client->user?->email;

                if (! is_string($email) || $email === '') {
                    return null;
                }

                return strtolower(trim($email));
            })
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function agenceEmails(): Collection
    {
        $ownerEmails = AgenceUser::query()
            ->where('est_proprietaire', true)
            ->where('statut', AgenceUser::STATUT_ACTIF)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter();

        $agenceIdsWithOwner = AgenceUser::query()
            ->where('est_proprietaire', true)
            ->pluck('agence_id');

        $fallbackEmails = Agence::query()
            ->where('statut', 'actif')
            ->whereNotIn('id', $agenceIdsWithOwner)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter();

        return $ownerEmails
            ->merge($fallbackEmails)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function parseManualEmails(string $raw): Collection
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        return collect($parts)
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
    }

    /**
     * @return list<string>
     */
    private function messageLines(string $message): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($message)) ?: [];

        return array_values(array_filter($lines, fn (string $line) => $line !== ''));
    }

    /**
     * @return array{label: string, url: string}|null
     */
    private function buildAction(?string $label, ?string $url): ?array
    {
        $label = is_string($label) ? trim($label) : '';
        $url = is_string($url) ? trim($url) : '';

        if ($label === '' || $url === '') {
            return null;
        }

        return [
            'label' => $label,
            'url' => $url,
        ];
    }
}
