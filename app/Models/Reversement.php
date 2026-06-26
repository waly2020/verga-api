<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reversement extends Model
{
    use HasUuids;

    protected $fillable = [
        'agence_id',
        'admin_id',
        'montant',
        'periode',
        'statut',
        'effectue_le',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'effectue_le' => 'datetime',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
