<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commande extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'offre_id',
        'agence_id',
        'code',
        'nom',
        'prenom',
        'telephone',
        'quantite',
        'quantite_payee',
        'capacite_bloquee',
        'montant_sous_total',
        'montant_commission_client',
        'montant_total',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'quantite_payee' => 'decimal:3',
            'capacite_bloquee' => 'boolean',
            'montant_sous_total' => 'decimal:2',
            'montant_commission_client' => 'decimal:2',
            'montant_total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class)->latestOfMany('created_at');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function colis(): HasMany
    {
        return $this->hasMany(Colis::class);
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    public function avis(): HasOne
    {
        return $this->hasOne(Avis::class);
    }

    public function quantiteRestante(): float
    {
        return max(0, (float) $this->quantite - (float) $this->quantite_payee);
    }

    public function isFullyPaid(): bool
    {
        return $this->quantiteRestante() <= 0.0001;
    }

    public function isReservation(): bool
    {
        return $this->quantiteRestante() > 0.0001;
    }
}
