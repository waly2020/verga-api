<?php

namespace Tests\Support;

use App\Models\Agence;
use App\Models\AgenceRole;
use App\Models\AgenceUser;

trait CreatesTestAgences
{
    /**
     * @return array{agence: Agence, owner: AgenceUser}
     */
    protected function createTestAgence(array $agenceAttributes = [], array $ownerAttributes = []): array
    {
        $agence = Agence::create(array_merge([
            'nom' => 'Transit Test',
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '0612345678',
            'statut' => 'actif',
        ], $agenceAttributes));

        $owner = AgenceUser::create(array_merge([
            'agence_id' => $agence->id,
            'agence_role_id' => AgenceRole::query()
                ->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)
                ->value('id'),
            'name' => 'Gérant Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'statut' => AgenceUser::STATUT_ACTIF,
            'est_proprietaire' => true,
        ], $ownerAttributes));

        return compact('agence', 'owner');
    }
}
