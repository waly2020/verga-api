<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agence;
use App\Models\AgenceUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgenceUserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Agence $agence, array $data): AgenceUser
    {
        return AgenceUser::create([
            'agence_id' => $agence->id,
            'agence_role_id' => $data['agence_role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'password' => $data['password'],
            'statut' => $data['statut'] ?? AgenceUser::STATUT_ACTIF,
            'est_proprietaire' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AgenceUser $user, array $data): AgenceUser
    {
        $this->ensureCollaborateur($user, 'modifier');

        $user->update($data);

        return $user;
    }

    public function delete(AgenceUser $user): void
    {
        $this->ensureCollaborateur($user, 'supprimer');

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });
    }

    private function ensureCollaborateur(AgenceUser $user, string $action): void
    {
        if ($user->est_proprietaire) {
            throw ValidationException::withMessages([
                'user' => ["Impossible de {$action} le propriétaire de l'agence."],
            ]);
        }
    }
}
