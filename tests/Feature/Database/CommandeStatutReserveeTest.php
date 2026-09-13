<?php

namespace Tests\Feature\Database;

use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTestAgences;
use Tests\TestCase;

class CommandeStatutReserveeTest extends TestCase
{
    use CreatesTestAgences;
    use RefreshDatabase;

    public function test_commande_accepte_le_statut_reservee(): void
    {
        ['agence' => $agence] = $this->createTestAgence();
        $offre = $this->createOffreForAgence($agence, [
            'titre' => 'Groupage',
            'type' => 'particulier',
            'prix' => 2500,
            'capacite_totale' => 100,
            'capacite_disponible' => 100,
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-RESERVEE-001',
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0600000000',
            'quantite' => 50,
            'quantite_payee' => 20,
            'montant_total' => 50000,
            'statut' => 'réservée',
        ]);

        $this->assertSame('réservée', $commande->fresh()->statut);
    }
}
