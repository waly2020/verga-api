<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AgenceUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AgenceUser extends Authenticatable
{
    /** @use HasFactory<AgenceUserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUT_ACTIF = 'actif';

    public const STATUT_SUSPENDU = 'suspendu';

    protected $fillable = [
        'agence_id',
        'agence_role_id',
        'name',
        'email',
        'telephone',
        'password',
        'statut',
        'est_proprietaire',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'est_proprietaire' => 'boolean',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AgenceRole::class, 'agence_role_id');
    }

    public function isActif(): bool
    {
        return $this->statut === self::STATUT_ACTIF;
    }
}
