<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id',
        'montant',
        'taux',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'taux'    => 'decimal:2',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
