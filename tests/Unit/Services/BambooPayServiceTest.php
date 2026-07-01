<?php

namespace Tests\Unit\Services;

use App\Http\Integrations\BambooPay\BambooPayConnector;
use App\Http\Integrations\BambooPay\Requests\CheckStatusRequest;
use App\Http\Integrations\BambooPay\Requests\InstantPaymentRequest;
use App\Http\Integrations\BambooPay\Requests\RedirectPaymentRequest;
use App\Services\BambooPayService;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

class BambooPayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bamboopay.base_url' => 'https://devfront-bamboopay.ventis.group',
            'bamboopay.merchant_id' => 'merchant-test',
            'bamboopay.username' => 'merchant-user',
            'bamboopay.password' => 'merchant-pass',
            'bamboopay.return_url' => 'https://verga.test/paiement/retour',
            'bamboopay.callback_url' => 'https://verga.test/api/v1/payments/bamboo-pay/callback',
        ]);
    }

    public function test_redirect_payment_returns_redirect_url(): void
    {
        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            RedirectPaymentRequest::class => MockResponse::make([
                'redirect_url' => 'https://devfront-bamboopay.ventis.group/pay/abc',
            ], 200),
        ]));

        $service = new BambooPayService($connector);

        $result = $service->redirectPayment([
            'payerName' => 'Jean Mbaye',
            'matricule' => 'CLI-001',
            'raisonSociale' => 'Particulier',
            'billingId' => 'CMD-001',
            'transactionAmount' => '10000',
            'phone' => '0612345678',
        ]);

        $this->assertSame('https://devfront-bamboopay.ventis.group/pay/abc', $result['redirect_url']);
    }

    public function test_instant_payment_returns_bamboo_reference(): void
    {
        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            InstantPaymentRequest::class => MockResponse::make([
                'reference_bp' => 'TXN-2025-000381',
                'reference' => 'CMD-001',
                'status' => true,
                'message' => null,
            ], 202),
        ]));

        $service = new BambooPayService($connector);

        $result = $service->instantPayment([
            'phone' => '0612345678',
            'amount' => '5000',
            'payer_name' => 'Jean Mbaye',
            'reference' => 'CMD-001',
            'operateur' => 'moov_money',
        ]);

        $this->assertTrue($result['status']);
        $this->assertSame('TXN-2025-000381', $result['reference_bp']);
    }

    public function test_check_status_uses_get_request(): void
    {
        $connector = new BambooPayConnector;
        $connector->withMockClient(new MockClient([
            CheckStatusRequest::class => MockResponse::make([
                'message' => 'OK',
                'code' => 200,
                'transaction' => [
                    'status' => 'completed',
                    'code' => 200,
                    'message' => 'Statut completed',
                ],
            ], 200),
        ]));

        $service = new BambooPayService($connector);

        $result = $service->checkStatus('TXN-2025-000381');

        $this->assertSame('completed', $result['transaction']['status']);

        $connector->getMockClient()?->assertSent(CheckStatusRequest::class);
        $connector->getMockClient()?->assertSent(function (CheckStatusRequest $request) {
            return $request->resolveEndpoint() === '/api/check-status/TXN-2025-000381';
        });
    }

    public function test_callback_route_accepts_bamboo_payload(): void
    {
        $response = $this->postJson('/api/v1/payments/bamboo-pay/callback', [
            'status' => 'completed',
            'reference' => 'TXN-2025-000381',
            'billingId' => 'CMD-001',
        ]);

        $response->assertOk()
            ->assertJsonPath('received', true)
            ->assertJsonPath('status', 'completed');
    }
}
