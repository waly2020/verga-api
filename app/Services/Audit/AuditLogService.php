<?php

namespace App\Services\Audit;

use App\Models\AgenceUser;
use App\Models\User;
use App\Support\Audit\AuditAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $actor
     */
    public function record(string $action, array $context = [], ?array $actor = null, ?string $level = null): void
    {
        try {
            $this->append([
                'id' => (string) Str::uuid(),
                'at' => now($this->timezone())->toIso8601String(),
                'action' => $action,
                'level' => $level ?? $this->defaultLevel($action),
                'actor' => $this->sanitize($actor ?? $this->resolveActor()),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'context' => $this->sanitize($context),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array{type: string, id: int|string|null, name: string|null, email: string|null, role: string|null, agence_id?: string|null}
     */
    public function resolveActor(): array
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return $this->actorFromUser($user);
        }

        if ($user instanceof AgenceUser) {
            return $this->actorFromUser($user);
        }

        return $this->systeme();
    }

    /**
     * @return array{type: string, id: int|string|null, name: string|null, email: string|null, role: string|null, agence_id?: string|null}
     */
    public function actorFromUser(object $user): array
    {
        if ($user instanceof AgenceUser) {
            return [
                'type' => 'agence',
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->relationLoaded('role') ? $user->role?->slug : null,
                'agence_id' => $user->agence_id,
            ];
        }

        if ($user instanceof User) {
            return [
                'type' => $user->isAdmin() ? 'admin' : ($user->isClient() ? 'client' : 'user'),
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ];
        }

        return $this->systeme();
    }

    /**
     * @return array{type: string, id: null, name: string, email: null, role: null}
     */
    public function systeme(): array
    {
        return [
            'type' => 'systeme',
            'id' => null,
            'name' => 'Système',
            'email' => null,
            'role' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function append(array $entry): void
    {
        $disk = Storage::disk($this->disk());
        $filename = $this->filenameFor(now($this->timezone())->toDateString());

        if (! $disk->exists($filename)) {
            $disk->put($filename, '');
        }

        $path = $disk->path($filename);
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

        $handle = fopen($path, 'ab');

        if ($handle === false) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            fwrite($handle, $line);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    public function filenameFor(string $date): string
    {
        return $date.'.json';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        $sensitive = config('verga.audit.sensitive_keys', []);

        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);

            if (collect($sensitive)->contains(fn (string $needle) => str_contains($normalized, $needle))) {
                $payload[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitize($value);
            }
        }

        return $payload;
    }

    private function defaultLevel(string $action): string
    {
        if (in_array($action, AuditAction::critical(), true)) {
            return 'critical';
        }

        if (str_contains($action, 'failed') || str_contains($action, 'deleted')) {
            return 'warning';
        }

        return 'info';
    }

    private function disk(): string
    {
        return (string) config('verga.audit.disk', 'audit');
    }

    private function timezone(): string
    {
        return (string) config('verga.audit.timezone', 'Africa/Libreville');
    }
}
