<?php

namespace App\Services;

use App\Models\Colis;
use App\Models\HistoriqueColis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ColisStatutService
{
    /** @var array<string, string> */
    public const FLUX = [
        'déposé' => 'en_transit',
        'en_transit' => 'arrivé',
        'arrivé' => 'récupéré',
    ];

    public function nextStatut(string $statutActuel): ?string
    {
        return self::FLUX[$statutActuel] ?? null;
    }

    public function advance(Colis $colis, User $user, ?string $statut = null, ?string $commentaire = null): Colis
    {
        return DB::transaction(function () use ($colis, $user, $statut, $commentaire) {
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
                'user_id' => $user->id,
                'statut' => $next,
                'commentaire' => $commentaire,
            ]);

            return $locked->fresh();
        });
    }
}
