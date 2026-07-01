<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colis extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id',
        'agence_id',
        'reference',
        'description',
        'poids',
        'volume',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'poids' => 'decimal:3',
            'volume' => 'decimal:3',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueColis::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ColisPhoto::class)->orderBy('ordre');
    }
}
