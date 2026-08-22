<?php

namespace App\Services;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Offre;
use App\Models\Publicite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PubliciteLifecycleService
{
    public function __construct(
        private readonly PublicitePricingService $pricing,
        private readonly PubliciteMediaService $media,
        private readonly PubliciteMailService $publiciteMail,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForAgence(Agence $agence, array $data, ?UploadedFile $image = null): Publicite
    {
        $this->assertOffreBelongsToAgence($agence->id, $data['offre_id'] ?? null);

        $publicite = Publicite::create([
            ...$this->attributesFrom($data),
            'agence_id' => $agence->id,
            'client_id' => null,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->attachImage($publicite, $image);

        $publicite = $publicite->fresh(['offre', 'agence', 'client']);

        $this->publiciteMail->notifySubmitted($publicite);

        return $publicite;
    }

    /**
     * Publicité créée par l'admin : publiée immédiatement, sans parcours de paiement.
     *
     * @param  array<string, mixed>  $data
     */
    public function createByAdmin(array $data, ?UploadedFile $image = null): Publicite
    {
        $proprietaire = $data['proprietaire'] ?? 'verga';
        $agenceId = $proprietaire === 'agence' ? ($data['agence_id'] ?? null) : null;
        $clientId = $proprietaire === 'client' ? ($data['client_id'] ?? null) : null;
        $offreId = $proprietaire === 'agence' ? ($data['offre_id'] ?? null) : null;

        if ($agenceId) {
            $this->assertOffreBelongsToAgence($agenceId, $offreId);
        } else {
            $offreId = null;
        }

        $data['offre_id'] = $offreId;

        $fin = Carbon::parse($data['date_fin'])->startOfDay();
        $expired = $fin->lt(now()->startOfDay());

        $publicite = Publicite::create([
            ...$this->attributesFrom($data),
            'agence_id' => $agenceId,
            'client_id' => $clientId,
            'statut' => $expired ? Publicite::STATUT_EXPIREE : Publicite::STATUT_PUBLIEE,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        $this->attachImage($publicite, $image);

        return $publicite->fresh(['offre', 'agence', 'client']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForClient(Client $client, array $data, ?UploadedFile $image = null): Publicite
    {
        unset($data['offre_id']);

        $publicite = Publicite::create([
            ...$this->attributesFrom($data),
            'client_id' => $client->id,
            'agence_id' => null,
            'offre_id' => null,
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'statut_paiement' => Publicite::PAIEMENT_NON_PAYE,
        ]);

        $this->attachImage($publicite, $image);

        $publicite = $publicite->fresh(['offre', 'agence', 'client']);

        $this->publiciteMail->notifySubmitted($publicite);

        return $publicite;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Publicite $publicite, array $data, ?UploadedFile $image = null): Publicite
    {
        if (! $publicite->isEditable()) {
            throw ValidationException::withMessages([
                'publicite' => ['Cette publicité ne peut plus être modifiée.'],
            ]);
        }

        if ($publicite->agence_id) {
            $this->assertOffreBelongsToAgence($publicite->agence_id, $data['offre_id'] ?? null);
        } else {
            unset($data['offre_id']);
            $data['offre_id'] = null;
        }

        $publicite->update($this->attributesFrom($data));
        $this->attachImage($publicite, $image);

        return $publicite->fresh(['offre', 'agence', 'client']);
    }

    public function resoumettre(Publicite $publicite): Publicite
    {
        if ($publicite->statut !== Publicite::STATUT_REFUSEE) {
            throw ValidationException::withMessages([
                'publicite' => ['Seule une publicité refusée peut être soumise à nouveau.'],
            ]);
        }

        $publicite->update([
            'statut' => Publicite::STATUT_EN_ATTENTE,
            'motif_refus' => null,
        ]);

        $publicite = $publicite->fresh(['offre', 'agence', 'client']);

        $this->publiciteMail->notifySubmitted($publicite);

        return $publicite;
    }

    public function valider(Publicite $publicite): Publicite
    {
        return $this->updateStatutByAdmin($publicite, Publicite::STATUT_VALIDEE);
    }

    private function applyValidation(Publicite $publicite): Publicite
    {
        $publicite->update([
            'statut' => Publicite::STATUT_VALIDEE,
            'motif_refus' => null,
        ]);

        $publicite = $publicite->fresh(['offre', 'agence', 'client']);

        $this->publiciteMail->notifyValidee($publicite);

        return $publicite;
    }

    private function applyRefus(Publicite $publicite, string $motif): Publicite
    {
        if ($motif === '') {
            throw ValidationException::withMessages([
                'motif' => ['Le motif du refus est obligatoire.'],
            ]);
        }

        $publicite->update([
            'statut' => Publicite::STATUT_REFUSEE,
            'motif_refus' => $motif,
        ]);

        $publicite = $publicite->fresh(['offre', 'agence', 'client']);

        $this->publiciteMail->notifyRefusee($publicite);

        return $publicite;
    }

    private function applyRetrait(Publicite $publicite, ?string $motif): Publicite
    {
        $publicite->update([
            'statut' => Publicite::STATUT_RETIREE,
            'motif_refus' => $motif ?: null,
        ]);

        $publicite = $publicite->fresh(['offre', 'agence', 'client']);

        $this->publiciteMail->notifyRetiree($publicite);

        return $publicite;
    }

    private function applyRepublication(Publicite $publicite): Publicite
    {
        if ($publicite->statut_paiement !== Publicite::PAIEMENT_PAYE) {
            throw ValidationException::withMessages([
                'statut' => ['Seule une publicité payée peut être republiée.'],
            ]);
        }

        $today = now()->startOfDay();
        $fin = Carbon::parse($publicite->date_fin)->startOfDay();

        if ($fin->lt($today)) {
            throw ValidationException::withMessages([
                'statut' => ['La date de fin est dépassée : prolongez la période avant de republier.'],
            ]);
        }

        $publicite->update([
            'statut' => Publicite::STATUT_PUBLIEE,
            'motif_refus' => null,
        ]);

        return $publicite->fresh(['offre', 'agence', 'client']);
    }

    public function refuser(Publicite $publicite, string $motif): Publicite
    {
        return $this->updateStatutByAdmin($publicite, Publicite::STATUT_REFUSEE, $motif);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function adminTransitionsMap(): array
    {
        return [
            Publicite::STATUT_EN_ATTENTE => [
                Publicite::STATUT_VALIDEE,
                Publicite::STATUT_REFUSEE,
                Publicite::STATUT_RETIREE,
            ],
            Publicite::STATUT_VALIDEE => [Publicite::STATUT_RETIREE],
            Publicite::STATUT_PUBLIEE => [Publicite::STATUT_RETIREE],
            Publicite::STATUT_RETIREE => [Publicite::STATUT_PUBLIEE],
            Publicite::STATUT_EXPIREE => [Publicite::STATUT_PUBLIEE],
        ];
    }

    /**
     * @return list<string>
     */
    public static function adminTransitionsFor(string $currentStatut): array
    {
        return self::adminTransitionsMap()[$currentStatut] ?? [];
    }

    public function updateStatutByAdmin(Publicite $publicite, string $statut, ?string $motif = null): Publicite
    {
        if (! in_array($statut, self::adminTransitionsFor($publicite->statut), true)) {
            throw ValidationException::withMessages([
                'statut' => ['Cette transition de statut n\'est pas autorisée.'],
            ]);
        }

        return match ($statut) {
            Publicite::STATUT_VALIDEE => $this->applyValidation($publicite),
            Publicite::STATUT_REFUSEE => $this->applyRefus($publicite, $motif ?? ''),
            Publicite::STATUT_RETIREE => $this->applyRetrait($publicite, $motif),
            Publicite::STATUT_PUBLIEE => $this->applyRepublication($publicite),
            default => throw ValidationException::withMessages([
                'statut' => ['Statut cible non pris en charge.'],
            ]),
        };
    }

    public function expireOverdue(): int
    {
        $publicites = Publicite::query()
            ->where('statut', Publicite::STATUT_PUBLIEE)
            ->whereDate('date_fin', '<', now()->toDateString())
            ->with(['agence', 'client.user', 'offre'])
            ->get();

        foreach ($publicites as $publicite) {
            $publicite->update(['statut' => Publicite::STATUT_EXPIREE]);
            $this->publiciteMail->notifyExpiree($publicite->fresh());
        }

        return $publicites->count();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        $debut = Carbon::parse($data['date_debut']);
        $fin = Carbon::parse($data['date_fin']);

        return [
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'lien' => $data['lien'] ?? null,
            'offre_id' => $data['offre_id'] ?? null,
            'date_debut' => $debut->toDateString(),
            'date_fin' => $fin->toDateString(),
            'nombre_jours' => $this->pricing->nombreJours($debut, $fin),
        ];
    }

    private function attachImage(Publicite $publicite, ?UploadedFile $image): void
    {
        if (! $image) {
            return;
        }

        $chemin = $this->media->storeImage($publicite, $image);
        $publicite->update([
            'image_chemin' => $chemin,
            'image_nom_original' => $image->getClientOriginalName(),
        ]);
    }

    private function assertOffreBelongsToAgence(string $agenceId, mixed $offreId): void
    {
        if ($offreId === null || $offreId === '') {
            return;
        }

        $belongs = Offre::query()
            ->whereKey($offreId)
            ->where('agence_id', $agenceId)
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'offre_id' => ['Vous ne pouvez rattacher que vos propres offres.'],
            ]);
        }
    }
}
