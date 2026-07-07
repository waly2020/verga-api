<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Models\Commande;
use App\Support\CommandeClientPresenter;
use Tests\TestCase;

class CommandeClientPresenterTest extends TestCase
{
    public function test_returns_registered_client_when_linked(): void
    {
        $client = new Client([
            'nom' => 'Obame',
            'prenom' => 'Paul',
            'email' => 'paul@test.com',
            'telephone' => '0611111111',
        ]);
        $client->id = 'client-uuid';

        $commande = new Commande(['client_id' => 'client-uuid']);
        $commande->setRelation('client', $client);

        $this->assertSame([
            'id' => 'client-uuid',
            'nom' => 'Obame',
            'prenom' => 'Paul',
            'email' => 'paul@test.com',
            'telephone' => '0611111111',
        ], CommandeClientPresenter::for($commande));
    }

    public function test_returns_guest_info_from_commande_when_no_client_linked(): void
    {
        $commande = new Commande([
            'client_id' => null,
            'nom' => 'Mbadinga',
            'prenom' => 'Jean',
            'telephone' => '0622222222',
        ]);

        $this->assertSame([
            'id' => null,
            'nom' => 'Mbadinga',
            'prenom' => 'Jean',
            'email' => null,
            'telephone' => '0622222222',
        ], CommandeClientPresenter::for($commande));
    }
}
