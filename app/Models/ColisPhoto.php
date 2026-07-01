<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColisPhoto extends Model
{
    use HasUuids;

    protected $fillable = [
        'colis_id',
        'chemin',
        'ordre',
    ];

    public function colis(): BelongsTo
    {
        return $this->belongsTo(Colis::class);
    }
}
