<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueColis extends Model
{
    use HasUuids;

    protected $fillable = [
        'colis_id',
        'user_id',
        'statut',
        'commentaire',
    ];

    public function colis(): BelongsTo
    {
        return $this->belongsTo(Colis::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
