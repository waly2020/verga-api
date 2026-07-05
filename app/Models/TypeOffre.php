<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeOffre extends Model
{
    use HasUuids;

    protected $table = 'types_offres';

    protected $fillable = [
        'slug',
        'nom',
        'description',
        'unite',
        'unite_label',
        'quantite_entier',
        'quantite_min',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'quantite_entier' => 'boolean',
            'quantite_min' => 'decimal:3',
            'actif' => 'boolean',
        ];
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
