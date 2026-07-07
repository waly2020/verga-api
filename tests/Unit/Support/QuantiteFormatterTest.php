<?php

namespace Tests\Unit\Support;

use App\Models\TypeOffre;
use App\Support\QuantiteFormatter;
use PHPUnit\Framework\TestCase;

class QuantiteFormatterTest extends TestCase
{
    public function test_format_quantite_avec_unite_kg(): void
    {
        $typeOffre = new TypeOffre([
            'unite' => 'kg',
            'quantite_entier' => false,
        ]);

        $this->assertSame('2 kg', QuantiteFormatter::format(2, $typeOffre));
        $this->assertSame('2,5 kg', QuantiteFormatter::format(2.5, $typeOffre));
    }

    public function test_format_quantite_conteneur_au_pluriel(): void
    {
        $typeOffre = new TypeOffre([
            'unite' => 'conteneur',
            'quantite_entier' => true,
        ]);

        $this->assertSame('1 conteneur', QuantiteFormatter::format(1, $typeOffre));
        $this->assertSame('2 conteneurs', QuantiteFormatter::format(2, $typeOffre));
    }

    public function test_colis_display_prefere_le_poids(): void
    {
        $typeOffre = new TypeOffre([
            'unite' => 'kg',
            'quantite_entier' => false,
        ]);

        $this->assertSame('15 kg', QuantiteFormatter::colisDisplay(15, 2, $typeOffre));
        $this->assertSame('2 kg', QuantiteFormatter::colisDisplay(null, 2, $typeOffre));
    }
}
