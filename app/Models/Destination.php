<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    use HasUuids;

    protected $fillable = [
        'depart',
        'arrivee',
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

    public function agences(): BelongsToMany
    {
        return $this->belongsToMany(Agence::class, 'agence_destination')
            ->withTimestamps();
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeAvailableForAgence(Builder $query, string $agenceId): Builder
    {
        return $query->whereHas('agences', fn (Builder $q) => $q->where('agences.id', $agenceId));
    }

    public function hasConfigurationAppliquee(): bool
    {
        return $this->appliquer_configuration
            && $this->montant !== null
            && $this->commission_pourcentage !== null;
    }
}
