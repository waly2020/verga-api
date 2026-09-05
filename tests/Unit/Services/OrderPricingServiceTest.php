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

        $pricing = app(OrderPricingService::class)->calculate($offre, 10);

        $this->assertSame(25000.0, $pricing['montant_sous_total']);
        $this->assertSame(0.0, $pricing['montant_commission_client']);
        $this->assertSame(25000.0, $pricing['montant_total']);
        $this->assertSame(2500.0, $pricing['prix_unitaire']);
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

        $pricing = app(OrderPricingService::class)->calculate($offre, 10);

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

        $pricing = app(OrderPricingService::class)->calculate($offre, 2);

        $this->assertSame(17500.0, $pricing['montant_sous_total']);
        $this->assertSame(500.0, $pricing['montant_commission_client']);
        $this->assertSame(18000.0, $pricing['montant_total']);
    }

    public function test_calculate_uses_matching_palier_for_all_units(): void
    {
        $offre = new Offre([
            'prix' => 3000,
            'paliers' => [
                ['min' => 1, 'max' => 2, 'prix' => 3000],
                ['min' => 3, 'max' => null, 'prix' => 2000],
            ],
        ]);

        $pricing = app(OrderPricingService::class)->calculate($offre, 5);

        $this->assertSame(2000.0, $pricing['prix_unitaire']);
        $this->assertSame(10000.0, $pricing['montant_sous_total']);
        $this->assertSame(10000.0, $pricing['montant_total']);
    }

    public function test_calculate_uses_reserved_quantity_to_select_palier(): void
    {
        $offre = new Offre([
            'prix' => 3000,
            'paliers' => [
                ['min' => 1, 'max' => 2, 'prix' => 3000],
                ['min' => 3, 'max' => null, 'prix' => 2000],
            ],
        ]);

        $pricing = app(OrderPricingService::class)->calculate($offre, 2, 5);

        $this->assertSame(2000.0, $pricing['prix_unitaire']);
        $this->assertSame(4000.0, $pricing['montant_sous_total']);
    }

    public function test_calculate_uses_client_grille_for_paid_amount(): void
    {
        $config = ConfigurationCommission::create([
            'destinataire' => 'client',
            'type' => 'grille',
            'valeur' => 0,
            'actif' => true,
        ]);
        $config->paliers()->createMany([
            ['montant_min' => 0, 'montant_max' => 9999, 'frais' => 1500, 'libelle' => null],
            ['montant_min' => 10000, 'montant_max' => null, 'frais' => 2500, 'libelle' => 'Frais Dossier'],
        ]);

        $offre = new Offre(['prix' => 3000]);

        $mini = app(OrderPricingService::class)->calculate($offre, 3);
        $this->assertSame(9000.0, $mini['montant_sous_total']);
        $this->assertSame(1500.0, $mini['montant_commission_client']);
        $this->assertSame(10500.0, $mini['montant_total']);

        $dossier = app(OrderPricingService::class)->calculate($offre, 4);
        $this->assertSame(12000.0, $dossier['montant_sous_total']);
        $this->assertSame(2500.0, $dossier['montant_commission_client']);
        $this->assertSame(14500.0, $dossier['montant_total']);
    }
}
