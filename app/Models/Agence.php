<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Agence extends Model
{
    use HasUuids;

    protected $fillable = [
        'type_agence_id',
        'nom',
        'email',
        'telephone',
        'adresse',
        'ville',
        'pays',
        'statut',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(AgenceUser::class);
    }

    public function proprietaire(): HasOne
    {
        return $this->hasOne(AgenceUser::class)->where('est_proprietaire', true);
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

    public function logo(): HasOne
    {
        return $this->hasOne(Logo::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
