<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offre extends Model
{
    use HasUuids;

    protected $fillable = [
        'agence_id',
        'titre',
        'description',
        'type',
        'type_offre_id',
        'prix',
        'capacite_totale',
        'capacite_disponible',
        'origine',
        'destination',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'capacite_totale' => 'decimal:3',
            'capacite_disponible' => 'decimal:3',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function typeOffre(): BelongsTo
    {
        return $this->belongsTo(TypeOffre::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }
}
