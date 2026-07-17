<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HistoriqueColis extends Model
{
    use HasUuids;

    protected $fillable = [
        'colis_id',
        'actor_type',
        'actor_id',
        'statut',
        'commentaire',
    ];

    public function colis(): BelongsTo
    {
        return $this->belongsTo(Colis::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
