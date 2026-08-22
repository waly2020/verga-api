<?php

namespace App\Http\Requests\Api\Agence;

use App\Http\Requests\Concerns\ValidatesPublicitePayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePubliciteRequest extends FormRequest
{
    use ValidatesPublicitePayload;

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
            ...$this->publiciteRules(imageRequired: true),
            'offre_id' => ['nullable', 'uuid', 'exists:offres,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->publiciteMessages();
    }
}
