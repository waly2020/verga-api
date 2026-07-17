<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgenceRole extends Model
{
    use HasUuids;

    public const SLUG_ADMIN_AGENCE = 'admin-agence';

    protected $fillable = [
        'slug',
        'nom',
        'description',
        'actif',
        'est_systeme',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'est_systeme' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(AgenceUser::class);
    }

    public function isSystem(): bool
    {
        return $this->est_systeme;
    }
}
