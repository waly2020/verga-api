<?php

namespace Database\Factories;

use App\Models\Agence;
use App\Models\AgenceRole;
use App\Models\AgenceUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AgenceUser>
 */
class AgenceUserFactory extends Factory
{
    protected $model = AgenceUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agence_id' => Agence::query()->inRandomOrder()->value('id')
                ?? Agence::create([
                    'nom' => fake()->company(),
                    'email' => fake()->unique()->companyEmail(),
                    'telephone' => fake()->numerify('06########'),
                    'statut' => 'actif',
                ])->id,
            'agence_role_id' => AgenceRole::query()->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)->value('id'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->numerify('06########'),
            'password' => Hash::make('password'),
            'statut' => AgenceUser::STATUT_ACTIF,
            'est_proprietaire' => false,
        ];
    }

    public function proprietaire(): static
    {
        return $this->state(fn () => [
            'est_proprietaire' => true,
            'agence_role_id' => AgenceRole::query()->where('slug', AgenceRole::SLUG_ADMIN_AGENCE)->value('id'),
        ]);
    }
}
