<?php

namespace Tests\Unit\Services;

use App\Models\ConfigurationCommission;
use App\Models\Offre;
use App\Services\OrderPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_without_active_client_commission(): void
    {
        $offre = new Offre(['prix' => 2500]);

        $pricing = (new OrderPricingService)->calculate($offre, 10);

        $this->assertSame(25000.0, $pricing['montant_sous_total']);
        $this->assertSame(0.0, $pricing['montant_commission_client']);
        $this->assertSame(25000.0, $pricing['montant_total']);
    }

    public function test_calculate_with_percentage_client_commission(): void
    {
        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'pourcentage',
            'valeur' => 5,
            'actif' => true,
        ]);

        $offre = new Offre(['prix' => 2500]);

        $pricing = (new OrderPricingService)->calculate($offre, 10);

        $this->assertSame(25000.0, $pricing['montant_sous_total']);
        $this->assertSame(1250.0, $pricing['montant_commission_client']);
        $this->assertSame(26250.0, $pricing['montant_total']);
    }

    public function test_calculate_with_fixed_client_commission(): void
    {
        ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'fixe',
            'valeur' => 500,
            'actif' => true,
        ]);

        $offre = new Offre(['prix' => 8750]);

        $pricing = (new OrderPricingService)->calculate($offre, 2);

        $this->assertSame(17500.0, $pricing['montant_sous_total']);
        $this->assertSame(500.0, $pricing['montant_commission_client']);
        $this->assertSame(18000.0, $pricing['montant_total']);
    }
}
