<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\UploadedFile;

class ClientMediaService
{
    /**
     * @param  array<int, array{fichier: UploadedFile, type_document: string}>  $documents
     * @return array<int, Document>
     */
    public function storeDocuments(Client $client, array $documents): array
    {
        $created = [];

        foreach ($documents as $document) {
            /** @var UploadedFile $file */
            $file = $document['fichier'];
            $path = $file->store("documents/clients/{$client->id}", 'public');

            $created[] = $client->documents()->create([
                'type_document' => $document['type_document'],
                'chemin' => $path,
                'nom_original' => $file->getClientOriginalName(),
            ]);
        }

        return $created;
    }
}
