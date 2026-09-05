<?php

namespace App\Http\Requests\Admin;

use App\Services\CommissionPaliersService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateConfigurationCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $destinataire = (string) $this->route('destinataire');
        $types = $destinataire === 'client'
            ? ['fixe', 'pourcentage', 'grille']
            : ['fixe', 'pourcentage'];

        $paliers = app(CommissionPaliersService::class);

        $rules = [
            'type' => ['required', Rule::in($types)],
            'actif' => ['sometimes', 'boolean'],
            'libelle' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->input('type') === 'grille') {
            return [...$rules, ...$paliers->rules()];
        }

        return [
            ...$rules,
            'valeur' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->input('type') === 'pourcentage', ['max:100']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de commission est obligatoire.',
            'type.in' => $this->route('destinataire') === 'client'
                ? 'Le type doit être fixe, pourcentage ou grille.'
                : 'Le type doit être fixe ou pourcentage.',
            'valeur.required' => 'La valeur est obligatoire.',
            'valeur.min' => 'La valeur ne peut pas être négative.',
            'valeur.max' => 'Le pourcentage ne peut pas dépasser 100 %.',
            ...app(CommissionPaliersService::class)->messages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== 'grille') {
            return;
        }

        $this->merge([
            'paliers' => app(CommissionPaliersService::class)->prepareInput($this->input('paliers')),
            'valeur' => 0,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') !== 'grille' || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $paliers = $this->input('paliers');
                app(CommissionPaliersService::class)->validateBusinessRules(
                    is_array($paliers) ? $paliers : null,
                    $validator,
                );
            },
        ];
    }
}
