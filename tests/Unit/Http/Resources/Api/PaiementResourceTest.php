<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources\Api;

use App\Http\Resources\Api\PaiementResource;
use App\Models\Commande;
use App\Models\Paiement;
use Tests\TestCase;

class PaiementResourceTest extends TestCase
{
    public function test_resource_returns_amount_operator_and_bamboo_reference(): void
    {
        $commande = new Commande(['code' => 'CMD-TEST-001']);

        $createdAt = now();

        $paiement = new Paiement([
            'code' => 'PAY-TEST-001',
            'montant_sous_total' => 5000,
            'montant_commission_client' => 250,
            'montant' => 5250,
            'operateur' => 'moov_money',
            'bamboo_reference' => 'TXN-001',
        ]);
        $paiement->created_at = $createdAt;

        $paiement->setRelation('commande', $commande);

        $data = PaiementResource::make($paiement)->resolve();

        $this->assertSame([
            'code' => 'PAY-TEST-001',
            'montant' => 5250.0,
            'operateur' => 'moov_money',
            'bamboo_reference' => 'TXN-001',
            'created_at' => $createdAt->toIso8601String(),
            'commande_code' => 'CMD-TEST-001',
        ], $data);
    }
}
