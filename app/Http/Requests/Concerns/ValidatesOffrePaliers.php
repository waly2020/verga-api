<?php

namespace App\Http\Requests\Concerns;

use App\Models\Destination;
use App\Services\OffrePaliersService;
use Illuminate\Validation\Validator;

trait ValidatesOffrePaliers
{
    /**
     * @return array<string, list<string>>
     */
    protected function paliersRules(bool $sometimes = false): array
    {
        return $this->paliersService()->rules($sometimes);
    }

    /**
     * @return array<string, string>
     */
    protected function paliersMessages(): array
    {
        return $this->paliersService()->messages();
    }

    protected function preparePaliersForValidation(): void
    {
        if (! $this->exists('paliers')) {
            return;
        }

        $this->merge([
            'paliers' => $this->paliersService()->prepareInput($this->input('paliers')),
        ]);
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $destinationId = $this->input('destination_id');
                $destination = is_string($destinationId)
                    ? Destination::query()->find($destinationId)
                    : null;

                $rawPaliers = $this->input('paliers');
                $paliers = is_array($rawPaliers)
                    ? array_values(array_filter($rawPaliers, 'is_array'))
                    : null;

                $this->paliersService()->validateBusinessRules(
                    $paliers,
                    $validator,
                    (bool) $destination?->appliquer_configuration,
                );
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function finalizePaliers(array $validated): array
    {
        if (! array_key_exists('paliers', $validated)) {
            return $validated;
        }

        if ($validated['paliers'] === null || $validated['paliers'] === []) {
            $validated['paliers'] = null;

            return $validated;
        }

        /** @var list<array<string, mixed>> $paliers */
        $paliers = $validated['paliers'];
        $validated['paliers'] = $this->paliersService()->normalizeStored($paliers);

        return $validated;
    }

    private function paliersService(): OffrePaliersService
    {
        return app(OffrePaliersService::class);
    }
}
