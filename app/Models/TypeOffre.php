<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeOffre extends Model
{
    use HasUuids;

    protected $table = 'types_offres';

    protected $fillable = [
        'agence_id',
        'slug',
        'nom',
        'description',
        'unite',
        'unite_label',
        'quantite_entier',
        'quantite_min',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'quantite_entier' => 'boolean',
            'quantite_min' => 'decimal:3',
            'actif' => 'boolean',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopePlatform(Builder $query): Builder
    {
        return $query->whereNull('agence_id');
    }

    public function scopeOwnedByAgence(Builder $query, string $agenceId): Builder
    {
        return $query->where('agence_id', $agenceId);
    }

    public function scopeAvailableForAgence(Builder $query, string $agenceId): Builder
    {
        return $query->where(function (Builder $query) use ($agenceId) {
            $query->whereNull('agence_id')
                ->orWhere('agence_id', $agenceId);
        });
    }

    public function isPlatform(): bool
    {
        return $this->agence_id === null;
    }

    public function isOwnedByAgence(string $agenceId): bool
    {
        return $this->agence_id === $agenceId;
    }
}
