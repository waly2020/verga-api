<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamation extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'commande_id',
        'agence_id',
        'nom',
        'prenom',
        'telephone',
        'email',
        'objet',
        'description',
        'statut',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }
}
