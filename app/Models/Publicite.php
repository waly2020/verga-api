<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Publicite extends Model
{
    use HasUuids;

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_VALIDEE = 'validée';

    public const STATUT_REFUSEE = 'refusée';

    public const STATUT_PUBLIEE = 'publiée';

    public const STATUT_EXPIREE = 'expirée';

    public const STATUT_RETIREE = 'retirée';

    public const PAIEMENT_NON_PAYE = 'non_payé';

    public const PAIEMENT_EN_ATTENTE = 'en_attente';

    public const PAIEMENT_PAYE = 'payé';

    public const PAIEMENT_ECHEC = 'échec';

    protected $fillable = [
        'agence_id',
        'client_id',
        'offre_id',
        'titre',
        'description',
        'lien',
        'image_chemin',
        'image_nom_original',
        'date_debut',
        'date_fin',
        'nombre_jours',
        'statut',
        'statut_paiement',
        'motif_refus',
    ];

    protected $attributes = [
        'statut' => self::STATUT_EN_ATTENTE,
        'statut_paiement' => self::PAIEMENT_NON_PAYE,
    ];

    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'nombre_jours' => 'integer',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementPublicite::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_chemin) {
            return null;
        }

        return Storage::disk('public')->url($this->image_chemin);
    }

    public function isEditable(): bool
    {
        return in_array($this->statut, [self::STATUT_EN_ATTENTE, self::STATUT_REFUSEE], true);
    }

    public function canBePaid(): bool
    {
        return $this->statut === self::STATUT_VALIDEE
            && in_array($this->statut_paiement, [self::PAIEMENT_NON_PAYE, self::PAIEMENT_ECHEC], true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where('statut', self::STATUT_PUBLIEE)
            ->whereDate('date_debut', '<=', $today)
            ->whereDate('date_fin', '>=', $today);
    }

    /**
     * @return list<string>
     */
    public static function statuts(): array
    {
        return [
            self::STATUT_EN_ATTENTE,
            self::STATUT_VALIDEE,
            self::STATUT_REFUSEE,
            self::STATUT_PUBLIEE,
            self::STATUT_EXPIREE,
            self::STATUT_RETIREE,
        ];
    }
}
