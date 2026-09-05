<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property list<array{min: float|int|string, max: float|int|string|null, prix: float|int|string}>|null $paliers
 */
class Offre extends Model
{
    use HasUuids;

    protected $fillable = [
        'agence_id',
        'destination_id',
        'titre',
        'description',
        'type',
        'type_offre_id',
        'prix',
        'paliers',
        'capacite_illimitee',
        'capacite_totale',
        'capacite_disponible',
        'date_depart',
        'date_depot_colis',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'paliers' => 'array',
            'capacite_illimitee' => 'boolean',
            'capacite_totale' => 'decimal:3',
            'capacite_disponible' => 'decimal:3',
            'date_depart' => 'date',
            'date_depot_colis' => 'date',
        ];
    }

    public function hasStockLimite(): bool
    {
        return ! $this->capacite_illimitee;
    }

    public function hasPaliers(): bool
    {
        return is_array($this->paliers) && $this->paliers !== [];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function typeOffre(): BelongsTo
    {
        return $this->belongsTo(TypeOffre::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function publicites(): HasMany
    {
        return $this->hasMany(Publicite::class);
    }

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }
}
