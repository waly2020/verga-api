<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaiementPublicite extends Model
{
    use HasUuids;

    protected $table = 'paiements_publicite';

    protected $fillable = [
        'publicite_id',
        'code',
        'nombre_jours',
        'prix_par_jour',
        'montant_sous_total',
        'montant_frais',
        'montant',
        'methode',
        'operateur',
        'reference',
        'bamboo_reference',
        'bamboo_message',
        'statut',
    ];

    protected $attributes = [
        'methode' => 'bamboo_redirect',
        'statut' => 'en_attente',
    ];

    protected function casts(): array
    {
        return [
            'nombre_jours' => 'integer',
            'prix_par_jour' => 'integer',
            'montant_sous_total' => 'integer',
            'montant_frais' => 'integer',
            'montant' => 'integer',
        ];
    }

    public function publicite(): BelongsTo
    {
        return $this->belongsTo(Publicite::class);
    }

    public function isFinalized(): bool
    {
        return in_array($this->statut, ['validé', 'échec'], true);
    }
}
