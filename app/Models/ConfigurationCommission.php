<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConfigurationCommission extends Model
{
    use HasUuids;

    protected $table = 'configurations_commission';

    protected $fillable = [
        'destinataire',
        'type',
        'valeur',
        'actif',
        'libelle',
    ];

    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public static function pour(string $destinataire): ?self
    {
        return static::query()
            ->where('destinataire', $destinataire)
            ->where('actif', true)
            ->first();
    }

    public function calculerMontant(float $montantBase): float
    {
        if (! $this->actif) {
            return 0.0;
        }

        if ($this->type === 'pourcentage') {
            return round($montantBase * ((float) $this->valeur / 100), 2);
        }

        return round((float) $this->valeur, 2);
    }

    public function estPourcentage(): bool
    {
        return $this->type === 'pourcentage';
    }

    public function estFixe(): bool
    {
        return $this->type === 'fixe';
    }
}
