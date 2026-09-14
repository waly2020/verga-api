<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Storage;

trait AssertsAuditLogs
{
    /**
     * @return list<array<string, mixed>>
     */
    protected function auditEntries(?string $action = null): array
    {
        $date = now((string) config('verga.audit.timezone'))->toDateString();
        $contents = Storage::disk('audit')->get($date.'.json') ?? '';
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

        if ($action === null) {
            return $entries;
        }

        return array_values(array_filter(
            $entries,
            fn (array $entry) => ($entry['action'] ?? null) === $action,
        ));
    }
}
