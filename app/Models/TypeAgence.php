<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAgence extends Model
{
    use HasUuids;

    protected $fillable = ['nom', 'description'];

    public function agences(): HasMany
    {
        return $this->hasMany(Agence::class);
    }
}
