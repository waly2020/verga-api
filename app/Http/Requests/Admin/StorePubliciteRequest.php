<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesPublicitePayload;
use App\Models\Offre;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePubliciteRequest extends FormRequest
{
    use ValidatesPublicitePayload;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->publiciteRules(imageRequired: true),
            'proprietaire' => ['required', Rule::in(['verga', 'agence', 'client'])],
            'agence_id' => ['required_if:proprietaire,agence', 'nullable', 'uuid', 'exists:agences,id'],
            'client_id' => ['required_if:proprietaire,client', 'nullable', 'uuid', 'exists:clients,id'],
            'offre_id' => ['nullable', 'uuid', 'exists:offres,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->publiciteMessages(),
            'proprietaire.required' => 'Le type d\'annonceur est obligatoire.',
            'agence_id.required_if' => 'Sélectionnez une agence.',
            'agence_id.exists' => 'Cette agence n\'existe pas.',
            'client_id.required_if' => 'Sélectionnez un client.',
            'client_id.exists' => 'Ce client n\'existe pas.',
            'offre_id.exists' => 'Cette offre n\'existe pas.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('proprietaire') !== 'agence' || ! $this->filled('offre_id')) {
                    return;
                }

                $belongs = Offre::query()
                    ->whereKey($this->input('offre_id'))
                    ->where('agence_id', $this->input('agence_id'))
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('offre_id', 'Cette offre n\'appartient pas à l\'agence sélectionnée.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $proprietaire = $this->input('proprietaire');

        $this->merge([
            'agence_id' => $proprietaire === 'agence' && $this->filled('agence_id') ? $this->input('agence_id') : null,
            'client_id' => $proprietaire === 'client' && $this->filled('client_id') ? $this->input('client_id') : null,
            'offre_id' => $proprietaire === 'agence' && $this->filled('offre_id') ? $this->input('offre_id') : null,
            'lien' => $this->filled('lien') ? $this->input('lien') : null,
            'description' => $this->filled('description') ? $this->input('description') : null,
        ]);
    }
}
