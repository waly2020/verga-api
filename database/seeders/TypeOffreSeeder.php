<?php

namespace Database\Seeders;

use App\Models\TypeOffre;
use Illuminate\Database\Seeder;

class TypeOffreSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'particulier',
                'nom' => 'Particulier (au kg)',
                'description' => 'Transport au kilogramme pour envois légers',
                'unite' => 'kg',
                'unite_label' => 'au kg',
                'quantite_entier' => false,
                'quantite_min' => 0.001,
                'actif' => true,
            ],
            [
                'slug' => 'metre_cube',
                'nom' => 'Mètre cube',
                'description' => 'Transport au mètre cube',
                'unite' => 'm3',
                'unite_label' => 'au m³',
                'quantite_entier' => false,
                'quantite_min' => 0.001,
                'actif' => true,
            ],
            [
                'slug' => 'conteneur',
                'nom' => 'Conteneur',
                'description' => 'Transport par conteneur',
                'unite' => 'conteneur',
                'unite_label' => 'par conteneur',
                'quantite_entier' => true,
                'quantite_min' => 1,
                'actif' => true,
            ],
        ];

        foreach ($types as $type) {
            TypeOffre::query()->updateOrCreate(
                ['slug' => $type['slug']],
                $type,
            );
        }
    }
}
