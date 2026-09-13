<?php

namespace App\Services;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\CheckStatusRequest;
use App\Http\Integrations\BambooPay\Requests\InstantPaymentRequest;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use App\Services\Audit\AuditLogService;
use App\Support\Audit\AuditAction;
use Saloon\Contracts\Body\HasBody;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class BambooPayService
{
    public function __construct(
        private ?BambooPayConnector $connector = null,
        private ?AuditLogService $audit = null,
    ) {
        $this->connector ??= app(BambooPayConnector::class);
        $this->audit ??= app(AuditLogService::class);
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
        $started = microtime(true);

        try {
            $response = $this->connector->send($request)->throw();

            $this->audit?->record(AuditAction::BambooRequest, [
                'method' => $request->getMethod()->value,
                'endpoint' => $request->resolveEndpoint(),
                'request' => $this->requestPayload($request),
                'response_status' => $response->status(),
                'response' => $this->responsePayload($response),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            return $response;
        } catch (Throwable $exception) {
            $this->audit?->record(AuditAction::BambooRequest, [
                'method' => $request->getMethod()->value,
                'endpoint' => $request->resolveEndpoint(),
                'request' => $this->requestPayload($request),
                ...$this->exceptionPayload($exception),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ], level: 'warning');

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function exceptionPayload(Throwable $exception): array
    {
        $payload = [
            'error' => $exception->getMessage(),
        ];

        if (! method_exists($exception, 'getResponse')) {
            return $payload;
        }

        try {
            $response = $exception->getResponse();
        } catch (Throwable) {
            return $payload;
        }

        if (! $response instanceof Response) {
            return $payload;
        }

        $payload['response_status'] = $response->status();
        $payload['response'] = $this->responsePayload($response);

        return $payload;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function responsePayload(Response $response): array|string|null
    {
        try {
            $json = $response->json();

            return is_array($json) ? $json : $response->body();
        } catch (Throwable) {
            return $response->body();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        if (! $request instanceof HasBody) {
            return [];
        }

        try {
            $body = $request->body();

            return method_exists($body, 'all') ? $body->all() : [];
        } catch (Throwable) {
            return [];
        }
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
