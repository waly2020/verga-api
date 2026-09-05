<?php

namespace App\Http\Requests\Concerns;

use App\Models\Ville;
use Illuminate\Support\Str;

trait NormalizesVilleFields
{
    protected function prepareVilleFields(): void
    {
        $this->merge([
            'pays' => Ville::reusePays((string) $this->input('pays')),
            'ville' => Str::of((string) $this->input('ville'))->squish()->toString(),
            'code' => Str::upper(Str::of((string) $this->input('code'))->squish()->toString()),
        ]);

        if ($this->has('actif')) {
            $this->merge([
                'actif' => $this->boolean('actif'),
            ]);
        }
    }
}
