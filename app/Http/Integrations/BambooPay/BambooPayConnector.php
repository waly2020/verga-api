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

    protected function defaultAuth(): ?Authenticator
    {
        return new BasicAuthenticator(
            (string) config('bamboopay.username'),
            (string) config('bamboopay.password'),
        );
    }
}
