<?php

namespace App\Services;

use App\Models\Agence;
use App\Models\Document;
use App\Models\Logo;
use Illuminate\Http\UploadedFile;

class AgenceMediaService
{
    public function storeLogo(Agence $agence, UploadedFile $file): Logo
    {
        $path = $file->store("logos/{$agence->id}", 'public');

        return $agence->logo()->updateOrCreate(
            ['agence_id' => $agence->id],
            [
                'chemin' => $path,
                'nom_original' => $file->getClientOriginalName(),
            ]
        );
    }

    /**
     * @param  array<int, array{fichier: UploadedFile, type_document: string}>  $documents
     * @return array<int, Document>
     */
    public function storeDocuments(Agence $agence, array $documents): array
    {
        $created = [];

        foreach ($documents as $document) {
            /** @var UploadedFile $file */
            $file = $document['fichier'];
            $path = $file->store("documents/agences/{$agence->id}", 'public');

            $created[] = $agence->documents()->create([
                'type_document' => $document['type_document'],
                'chemin' => $path,
                'nom_original' => $file->getClientOriginalName(),
            ]);
        }

        return $created;
    }
}
