<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'type_document',
        'chemin',
        'nom_original',
    ];

    protected $appends = [
        'url',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->chemin);
    }
}
