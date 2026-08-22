<?php

namespace App\Services;

use App\Models\Publicite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PubliciteMediaService
{
    public function storeImage(Publicite $publicite, UploadedFile $file): string
    {
        $this->deleteImage($publicite);

        return $file->store("publicites/{$publicite->id}", 'public');
    }

    public function deleteImage(Publicite $publicite): void
    {
        if ($publicite->image_chemin) {
            Storage::disk('public')->delete($publicite->image_chemin);
        }
    }
}
