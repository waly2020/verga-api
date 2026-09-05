<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    use HasUuids;

    protected $fillable = [
        'ville_depart_id',
        'ville_arrivee_id',
        'montant',
        'commission_pourcentage',
        'appliquer_configuration',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'commission_pourcentage' => 'decimal:2',
            'appliquer_configuration' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function villeDepart(): BelongsTo
    {
        return $this->belongsTo(Ville::class, 'ville_depart_id');
    }

    public function villeArrivee(): BelongsTo
    {
        return $this->belongsTo(Ville::class, 'ville_arrivee_id');
    }

    public function agences(): BelongsToMany
    {
        return $this->belongsToMany(Agence::class, 'agence_destination')
            ->withTimestamps();
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }

    public function trajetLabel(): string
    {
        $this->loadMissing(['villeDepart', 'villeArrivee']);

        return ($this->villeDepart?->label() ?? '?').' → '.($this->villeArrivee?->label() ?? '?');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeAvailableForAgence(Builder $query, string $agenceId): Builder
    {
        return $query->whereHas('agences', fn (Builder $q) => $q->where('agences.id', $agenceId));
    }

    public function scopeMatchingLocalite(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $match = function (Builder $pq) use ($term): void {
                $pq->where(function (Builder $inner) use ($term) {
                    $inner->where('ville', 'like', "%{$term}%")
                        ->orWhere('pays', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            };

            $q->whereHas('villeDepart', $match)
                ->orWhereHas('villeArrivee', $match);
        });
    }

    public function scopeOrderByTrajet(Builder $query): Builder
    {
        $villesTable = (new Ville)->getTable();

        return $query
            ->orderBy(
                Ville::query()->select('ville')->whereColumn("{$villesTable}.id", 'destinations.ville_depart_id')
            )
            ->orderBy(
                Ville::query()->select('ville')->whereColumn("{$villesTable}.id", 'destinations.ville_arrivee_id')
            );
    }

    public function hasConfigurationAppliquee(): bool
    {
        return $this->appliquer_configuration
            && $this->montant !== null
            && $this->commission_pourcentage !== null;
    }
}
