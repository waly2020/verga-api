<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agence extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type_agence_id',
        'nom',
        'email',
        'telephone',
        'adresse',
        'ville',
        'pays',
        'statut',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeAgence(): BelongsTo
    {
        return $this->belongsTo(TypeAgence::class);
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }

    public function typesOffres(): HasMany
    {
        return $this->hasMany(TypeOffre::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function colis(): HasMany
    {
        return $this->hasMany(Colis::class);
    }

    public function reversements(): HasMany
    {
        return $this->hasMany(Reversement::class);
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }
}
