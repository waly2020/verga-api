<?php

namespace App\Http\Integrations\BambooPay;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;

class BambooPayConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return (string) config('bamboopay.base_url');
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [
            'timeout' => 15,
            'connect_timeout' => 5,
        ];
    }

    protected function defaultAuth(): ?Authenticator
    {
        return new BasicAuthenticator(
            (string) config('bamboopay.username'),
            (string) config('bamboopay.password'),
        );
    }
}
