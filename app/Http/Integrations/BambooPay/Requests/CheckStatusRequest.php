<?php

namespace App\Http\Integrations\BambooPay\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CheckStatusRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $transactionId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/check-status/'.urlencode($this->transactionId);
    }
}
