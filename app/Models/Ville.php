<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Ville extends Model
{
    use HasUuids;

    protected $fillable = [
        'pays',
        'ville',
        'code',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function destinationsDepart(): HasMany
    {
        return $this->hasMany(Destination::class, 'ville_depart_id');
    }

    public function destinationsArrivee(): HasMany
    {
        return $this->hasMany(Destination::class, 'ville_arrivee_id');
    }

    public function isUsedByDestinations(): bool
    {
        return $this->destinationsDepart()->exists()
            || $this->destinationsArrivee()->exists();
    }

    public function label(): string
    {
        return "{$this->ville} ({$this->pays})";
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeDuPays(Builder $query, string $pays): Builder
    {
        return $query->whereRaw('lower(pays) = ?', [Str::lower($pays)]);
    }

    /**
     * @return Collection<int, string>
     */
    public static function nomsPays(bool $actifsUniquement = true): Collection
    {
        return static::query()
            ->when($actifsUniquement, fn (Builder $query) => $query->actif())
            ->select('pays')
            ->distinct()
            ->orderBy('pays')
            ->pluck('pays');
    }

    public static function reusePays(string $pays): string
    {
        $normalized = Str::of($pays)->squish()->toString();

        if ($normalized === '') {
            return '';
        }

        $existing = static::query()
            ->whereRaw('lower(pays) = ?', [Str::lower($normalized)])
            ->value('pays');

        return is_string($existing) && $existing !== '' ? $existing : $normalized;
    }
}
