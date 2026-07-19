<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Colis;
use App\Models\HistoriqueColis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ColisStatutService
{
    /** @var array<string, string> */
    public const FLUX = [
        'chez_client' => 'déposé',
        'déposé' => 'en_transit',
        'en_transit' => 'arrivé',
        'arrivé' => 'récupéré',
    ];

    public function nextStatut(string $statutActuel): ?string
    {
        return self::FLUX[$statutActuel] ?? null;
    }

    public function advance(Colis $colis, Model $actor, ?string $statut = null, ?string $commentaire = null, ?string $dateStatut = null): Colis
    {
        return DB::transaction(function () use ($colis, $actor, $statut, $commentaire, $dateStatut) {
            /** @var Colis $locked */
            $locked = Colis::query()
                ->whereKey($colis->id)
                ->lockForUpdate()
                ->firstOrFail();

            $next = $this->nextStatut($locked->statut);

            if (! $next) {
                throw ValidationException::withMessages([
                    'statut' => ['Ce colis est dans son statut final.'],
                ]);
            }

            if ($statut !== null && $statut !== $next) {
                throw ValidationException::withMessages([
                    'statut' => ["Le statut suivant autorisé est « {$next} »."],
                ]);
            }

            $locked->update(['statut' => $next]);

            HistoriqueColis::create([
                'colis_id' => $locked->id,
                'actor_type' => $actor->getMorphClass(),
                'actor_id' => $actor->getKey(),
                'statut' => $next,
                'date_statut' => $dateStatut,
                'commentaire' => $commentaire,
            ]);

            return $locked->fresh();
        });
    }
}
