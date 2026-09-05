<?php

namespace App\Jobs;

use App\Services\OffreExpirationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DesactiverOffresDepartPassees implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [1, 5, 10];

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function handle(OffreExpirationService $expiration): void
    {
        $count = $expiration->desactiverDepartPasses();

        Log::info('Offres désactivées après date de départ', ['count' => $count]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Échec de la désactivation des offres à date de départ passée', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
