<?php

namespace Tests\Feature;

use App\Models\Agence;
use App\Models\Client;
use App\Models\Logo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoAndDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agence_can_have_a_logo(): void
    {
        $agence = $this->createAgence();

        $logo = Logo::create([
            'agence_id' => $agence->id,
            'chemin' => "logos/{$agence->id}/logo.png",
            'nom_original' => 'logo.png',
        ]);

        $this->assertDatabaseHas('logos', [
            'id' => $logo->id,
            'agence_id' => $agence->id,
            'chemin' => "logos/{$agence->id}/logo.png",
        ]);

        $this->assertTrue($agence->fresh()->logo->is($logo));
    }

    public function test_client_and_agence_can_have_documents_with_free_type(): void
    {
        $agence = $this->createAgence();
        $client = Client::create([
            'user_id' => User::factory()->create(['role' => 'client'])->id,
            'nom' => 'Mbadinga',
            'prenom' => 'Alain',
            'email' => 'alain@test.com',
            'telephone' => '0611223344',
            'type' => 'particulier',
            'statut' => 'actif',
        ]);

        $docAgence = $agence->documents()->create([
            'type_document' => 'registre_commerce',
            'chemin' => "documents/agences/{$agence->id}/rc.pdf",
            'nom_original' => 'rc.pdf',
        ]);

        $docClient = $client->documents()->create([
            'type_document' => 'piece_identite',
            'chemin' => "documents/clients/{$client->id}/cni.jpg",
            'nom_original' => 'cni.jpg',
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $docAgence->id,
            'documentable_type' => 'agence',
            'documentable_id' => $agence->id,
            'type_document' => 'registre_commerce',
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $docClient->id,
            'documentable_type' => 'client',
            'documentable_id' => $client->id,
            'type_document' => 'piece_identite',
        ]);

        $this->assertCount(1, $agence->fresh()->documents);
        $this->assertCount(1, $client->fresh()->documents);
        $this->assertInstanceOf(Agence::class, $docAgence->fresh()->documentable);
        $this->assertInstanceOf(Client::class, $docClient->fresh()->documentable);
    }

    private function createAgence(): Agence
    {
        return Agence::create([
            'user_id' => User::factory()->create(['role' => 'agence'])->id,
            'nom' => 'Transit Test',
            'email' => fake()->unique()->safeEmail(),
            'telephone' => '0611111111',
            'statut' => 'actif',
        ]);
    }
}
