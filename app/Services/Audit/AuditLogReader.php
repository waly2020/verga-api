<?php

namespace App\Services\Audit;

use App\Support\Audit\AuditAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class AuditLogReader
{
    public function __construct(
        private readonly AuditLogService $writer,
    ) {}

    /**
     * @return list<string>
     */
    public function days(): array
    {
        return collect(Storage::disk($this->disk())->files())
            ->filter(fn (string $file) => str_ends_with($file, '.json'))
            ->map(fn (string $file) => basename($file, '.json'))
            ->filter(fn (string $date) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->sortDesc()
            ->values()
            ->all();
    }

    public function exists(string $date): bool
    {
        return Storage::disk($this->disk())->exists($this->writer->filenameFor($date));
    }

    public function absolutePath(string $date): string
    {
        return Storage::disk($this->disk())->path($this->writer->filenameFor($date));
    }

    /**
     * @param  array{date?: string, action?: string, search?: string, page?: int, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $date = $filters['date'] ?? now((string) config('verga.audit.timezone'))->toDateString();
        $entries = $this->entries($date);
        $action = $filters['action'] ?? null;
        $search = $filters['search'] ?? null;

        if (is_string($action) && $action !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry) => ($entry['action'] ?? '') === $action
                    || str_starts_with((string) ($entry['action'] ?? ''), $action.'.'),
            ));
        }

        if (is_string($search) && $search !== '') {
            $needle = mb_strtolower($search);
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry) => str_contains(
                    mb_strtolower((string) json_encode($entry, JSON_UNESCAPED_UNICODE)),
                    $needle,
                ),
            ));
        }

        $entries = array_reverse($entries);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 30)));
        $total = count($entries);
        $slice = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            array_map(fn (array $entry) => [
                ...$entry,
                'label' => AuditAction::label((string) ($entry['action'] ?? '')),
            ], $slice),
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function entries(string $date): array
    {
        if (! $this->exists($date)) {
            return [];
        }

        $contents = Storage::disk($this->disk())->get($this->writer->filenameFor($date)) ?? '';
        $entries = [];

        foreach (preg_split("/\r\n|\n|\r/", $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    private function disk(): string
    {
        return (string) config('verga.audit.disk', 'audit');
    }
}
