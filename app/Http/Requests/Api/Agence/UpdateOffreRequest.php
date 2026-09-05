<?php

namespace App\Http\Requests\Api\Agence;

use App\Http\Requests\Concerns\ValidatesOffrePaliers;
use App\Models\Destination;
use App\Models\Offre;
use App\Services\DestinationResolver;
use App\Services\OffreDestinationConfigService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOffreRequest extends FormRequest
{
    use ValidatesOffrePaliers;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination_id' => [
                'required',
                'uuid',
                Rule::exists('destinations', 'id')->where(fn ($query) => $query->where('actif', true)),
            ],
            'titre' => ['required', 'string', 'max:255'],
            'type_offre_id' => ['required_without:type', 'uuid', 'exists:types_offres,id'],
            'type' => ['required_without:type_offre_id', 'string', 'max:50'],
            'prix' => ['required', 'numeric', 'min:0'],
            ...$this->paliersRules(sometimes: true),
            'capacite_illimitee' => ['sometimes', 'boolean'],
            'capacite_totale' => ['required_unless:capacite_illimitee,true', 'nullable', 'numeric', 'min:0.001'],
            'date_depart' => ['nullable', 'date'],
            'date_depot_colis' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'statut' => ['required', Rule::in(['active', 'inactive', 'archivée'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination_id.required' => 'La destination est obligatoire.',
            'destination_id.exists' => "Cette destination n'existe pas ou est inactive.",
            'titre.required' => 'Le titre est obligatoire.',
            'type_offre_id.required_without' => 'Le type d\'offre est obligatoire.',
            'type.required_without' => 'Le type est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'capacite_totale.required_unless' => 'La capacité totale est obligatoire pour une offre à stock limité.',
            'statut.required' => 'Le statut est obligatoire.',
            ...$this->paliersMessages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $illimitee = $this->has('capacite_illimitee')
            ? $this->boolean('capacite_illimitee')
            : $this->existingIllimitee();

        $this->merge(['capacite_illimitee' => $illimitee]);

        if ($illimitee) {
            $this->merge(['capacite_totale' => null]);
        }

        $this->preparePaliersForValidation();
    }

    /**
     * @param  array<string, mixed>|int|string|null  $key
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function validated($key = null, $default = null): mixed
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated();

        $destination = Destination::query()->findOrFail($validated['destination_id']);
        $agenceId = (string) $this->user()->agence_id;

        app(DestinationResolver::class)->ensureAvailableForAgence($destination, $agenceId);
        $validated = $this->finalizePaliers($validated);
        $validated = app(OffreDestinationConfigService::class)->apply($validated, $destination);

        return is_null($key) ? $validated : data_get($validated, $key, $default);
    }

    private function existingIllimitee(): bool
    {
        $offre = $this->route('offre');

        if ($offre instanceof Offre) {
            return (bool) $offre->capacite_illimitee;
        }

        if (is_string($offre)) {
            return (bool) Offre::query()->whereKey($offre)->value('capacite_illimitee');
        }

        return false;
    }
}
