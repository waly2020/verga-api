<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPalier extends Model
{
    use HasUuids;

    protected $fillable = [
        'configuration_commission_id',
        'montant_min',
        'montant_max',
        'frais',
        'libelle',
    ];

    protected function casts(): array
    {
        return [
            'montant_min' => 'decimal:2',
            'montant_max' => 'decimal:2',
            'frais' => 'decimal:2',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(ConfigurationCommission::class, 'configuration_commission_id');
    }

    public function couvre(float $montant): bool
    {
        $min = (float) $this->montant_min;
        $max = $this->montant_max === null ? null : (float) $this->montant_max;

        return $montant + 0.001 >= $min && ($max === null || $montant <= $max + 0.001);
    }
}
