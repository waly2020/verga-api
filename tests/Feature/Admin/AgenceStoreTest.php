<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgenceStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_agence_with_logo_and_documents(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/agences', [
            'nom' => 'Transit Admin Media',
            'email' => 'contact@transit-admin.test',
            'telephone' => '0612345678',
            'ville' => 'Libreville',
            'pays' => 'Gabon',
            'gerant_name' => 'Jean Mbaye',
            'gerant_email' => 'gerant@transit-admin.test',
            'gerant_password' => 'password',
            'gerant_password_confirmation' => 'password',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'documents' => [
                [
                    'fichier' => UploadedFile::fake()->create('rc.pdf', 100, 'application/pdf'),
                    'type_document' => 'registre_commerce',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('agences', [
            'email' => 'contact@transit-admin.test',
            'nom' => 'Transit Admin Media',
        ]);
        $this->assertDatabaseHas('agence_users', [
            'email' => 'gerant@transit-admin.test',
            'est_proprietaire' => true,
        ]);
        $this->assertDatabaseCount('logos', 1);
        $this->assertDatabaseHas('documents', [
            'type_document' => 'registre_commerce',
            'documentable_type' => 'agence',
        ]);
    }
}
