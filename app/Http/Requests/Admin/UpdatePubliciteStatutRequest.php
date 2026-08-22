<?php

namespace App\Http\Requests\Admin;

use App\Models\Publicite;
use App\Services\PubliciteLifecycleService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePubliciteStatutRequest extends FormRequest
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
        /** @var Publicite $publicite */
        $publicite = $this->route('publicite');
        $allowed = PubliciteLifecycleService::adminTransitionsFor($publicite->statut);

        return [
            'statut' => ['required', Rule::in($allowed)],
            'motif' => [
                Rule::requiredIf($this->input('statut') === Publicite::STATUT_REFUSEE),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Cette transition de statut n\'est pas autorisée.',
            'motif.required' => 'Le motif est obligatoire pour un refus.',
        ];
    }
}
