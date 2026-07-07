<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\Finance\AgenceSoldeService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReversementRequest extends FormRequest
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
        return [
            'agence_id' => ['required', 'uuid', 'exists:agences,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'periode' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $agenceId = (string) $this->input('agence_id');
            $montant = (float) $this->input('montant');

            if (! AgenceSoldeService::peutReverser($agenceId, $montant)) {
                $validator->errors()->add(
                    'montant',
                    'Le montant dépasse le solde disponible de l\'agence ('.number_format(AgenceSoldeService::soldeDisponible($agenceId), 0, ',', ' ').' FCFA).',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agence_id.required' => 'L\'agence est obligatoire.',
            'agence_id.exists' => 'L\'agence sélectionnée est invalide.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant doit être supérieur à zéro.',
            'periode.required' => 'La période est obligatoire.',
            'periode.regex' => 'La période doit être au format AAAA-MM.',
        ];
    }
}
