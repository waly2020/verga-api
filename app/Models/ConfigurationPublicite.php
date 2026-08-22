<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConfigurationPublicite extends Model
{
    use HasUuids;

    protected $table = 'configurations_publicite';

    protected $fillable = [
        'prix_par_jour',
        'type_frais',
        'valeur_frais',
        'actif',
        'libelle',
    ];

    protected $attributes = [
        'type_frais' => 'pourcentage',
        'valeur_frais' => 0,
        'actif' => false,
    ];

    protected function casts(): array
    {
        return [
            'prix_par_jour' => 'integer',
            'valeur_frais' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public static function actuelle(): ?self
    {
        return static::query()->where('actif', true)->first()
            ?? static::query()->first();
    }

    public function calculerFrais(int $montantHorsFrais): int
    {
        if (! $this->actif) {
            return 0;
        }

        if ($this->type_frais === 'pourcentage') {
            return (int) round($montantHorsFrais * ((int) $this->valeur_frais / 100));
        }

        return (int) $this->valeur_frais;
    }
}
