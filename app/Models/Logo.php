<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Logo extends Model
{
    use HasUuids;

    protected $fillable = [
        'agence_id',
        'chemin',
        'nom_original',
    ];

    protected $appends = [
        'url',
    ];

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->chemin);
    }
}
