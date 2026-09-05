<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesVilleFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVilleRequest extends FormRequest
{
    use NormalizesVilleFields;

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
            'pays' => ['required', 'string', 'max:255'],
            'ville' => [
                'required',
                'string',
                'max:255',
                Rule::unique('villes', 'ville')->where(
                    fn ($query) => $query->where('pays', $this->input('pays'))
                ),
            ],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('villes', 'code')],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pays.required' => 'Le pays est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'code.required' => 'Le code est obligatoire.',
            'code.regex' => 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'ville.unique' => 'Cette ville existe déjà pour ce pays.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareVilleFields();
    }
}
