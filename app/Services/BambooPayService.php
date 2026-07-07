<?php

namespace App\Services;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\CheckStatusRequest;
use App\Http\Integrations\BambooPay\Requests\InstantPaymentRequest;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

class BambooPayService
{
    public function __construct(
        private ?BambooPayConnector $connector = null,
    ) {
        $this->connector ??= app(BambooPayConnector::class);
    }

    /**
     * Paiement avec redirection vers la plateforme Bamboo Pay.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redirectPayment(array $data): array
    {
        return $this->send(new RedirectPaymentRequest(
            $this->mergeDefaults($data, [
                'update_status_url' => config('bamboopay.callback_url'),
            ])
        ))->json();
    }

    /**
     * Paiement instantané (Moov / Airtel) sans redirection.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function instantPayment(array $data): array
    {
        return $this->send(new InstantPaymentRequest(
            $this->mergeDefaults($data, [
                'callback_url' => config('bamboopay.callback_url'),
            ])
        ))->json();
    }

    /**
     * Vérification du statut d'une transaction (GET).
     *
     * @return array<string, mixed>
     */
    public function checkStatus(string $transactionId): array
    {
        return $this->send(new CheckStatusRequest($transactionId))->json();
    }

    private function send(Request $request): Response
    {
        return $this->connector->send($request)->throw();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function mergeDefaults(array $data, array $defaults): array
    {
        $payload = array_merge($defaults, $data);

        $payload['merchant_id'] ??= config('bamboopay.merchant_id');

        return array_filter(
            $payload,
            fn (mixed $value) => $value !== null && $value !== '',
        );
    }
}
