<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id',
        'code',
        'quantite',
        'montant',
        'montant_sous_total',
        'montant_commission_client',
        'montant_commission_agence',
        'montant_agence',
        'methode',
        'operateur',
        'reference',
        'bamboo_reference',
        'bamboo_message',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'montant' => 'decimal:2',
            'montant_sous_total' => 'decimal:2',
            'montant_commission_client' => 'decimal:2',
            'montant_commission_agence' => 'decimal:2',
            'montant_agence' => 'decimal:2',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function isFinalized(): bool
    {
        return in_array($this->statut, ['validé', 'échec', 'remboursé'], true);
    }
}
