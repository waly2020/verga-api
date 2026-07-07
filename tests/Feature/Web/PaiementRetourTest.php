<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Agence;
use App\Models\Colis;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Models\User;
use App\Support\PaiementReturnUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaiementRetourTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{commande: Commande, paiement: Paiement}
     */
    private function createPaiementScenario(): array
    {
        $user = User::factory()->create(['role' => 'agence']);
        $agence = Agence::create([
            'user_id' => $user->id,
            'nom' => 'Transit Test',
            'email' => 'agence@test.com',
            'telephone' => '0611111111',
            'statut' => 'actif',
            'ville' => 'Libreville',
        ]);

        $offre = Offre::create([
            'agence_id' => $agence->id,
            'titre' => 'Groupage Paris',
            'type' => 'particulier',
            'prix' => 2500,
            'capacite_totale' => 1000,
            'capacite_disponible' => 1000,
            'origine' => 'Libreville',
            'destination' => 'Paris',
            'statut' => 'active',
        ]);

        $commande = Commande::create([
            'offre_id' => $offre->id,
            'agence_id' => $agence->id,
            'code' => 'CMD-RETOUR-001',
            'nom' => 'Mbadinga',
            'prenom' => 'Jean',
            'telephone' => '0622222222',
            'quantite' => 2,
            'quantite_payee' => 2,
            'montant_sous_total' => 5000,
            'montant_commission_client' => 250,
            'montant_total' => 5250,
            'statut' => 'confirmée',
        ]);

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'code' => 'PAY-RETOUR-001',
            'quantite' => 2,
            'montant_sous_total' => 5000,
            'montant_commission_client' => 250,
            'montant' => 5250,
            'methode' => 'bamboo_redirect',
            'statut' => 'validé',
            'bamboo_reference' => 'TXN-TEST-001',
        ]);

        Colis::create([
            'commande_id' => $commande->id,
            'agence_id' => $agence->id,
            'reference' => 'COL-RETOUR-001',
            'description' => 'Cartons vêtements',
            'statut' => 'chez_client',
        ]);

        return compact('commande', 'paiement');
    }

    public function test_return_url_includes_query_anchor_for_bamboo_params(): void
    {
        $paiement = Paiement::make(['code' => 'PAY-2025-AA']);

        $this->assertSame(
            url('/paiement/PAY-2025-AA/retour?ref=PAY-2025-AA'),
            PaiementReturnUrl::for($paiement),
        );
    }

    public function test_paiement_return_page_accepts_bamboo_query_params(): void
    {
        ['paiement' => $paiement] = $this->createPaiementScenario();

        $this->get("/paiement/{$paiement->code}/retour?ref={$paiement->code}&status=failed&ref={$paiement->code}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('paiement/retour'));
    }

    public function test_paiement_return_page_accepts_malformed_bamboo_url(): void
    {
        ['paiement' => $paiement] = $this->createPaiementScenario();

        $this->get("/paiement/{$paiement->code}/retour&status=failed&ref={$paiement->code}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('paiement/retour'));
    }

    public function test_paiement_return_page_renders_recap(): void
    {
        ['paiement' => $paiement] = $this->createPaiementScenario();

        $this->get("/paiement/{$paiement->code}/retour")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('paiement/retour')
                ->where('paiement.code', 'PAY-RETOUR-001')
                ->where('commande.code', 'CMD-RETOUR-001')
                ->has('colis', 1)
                ->has('facture_url'));
    }

    public function test_paiement_facture_pdf_is_downloadable(): void
    {
        ['paiement' => $paiement] = $this->createPaiementScenario();

        $response = $this->get("/paiement/{$paiement->code}/facture");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition'),
        );
    }
}
