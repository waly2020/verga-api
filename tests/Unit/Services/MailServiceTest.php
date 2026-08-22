<?php

namespace Tests\Unit\Services;

use App\Mail\TransactionalMail;
use App\Services\MailService;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

class MailServiceTest extends TestCase
{
    public function test_send_transactional_mail(): void
    {
        Mail::fake();

        app(MailService::class)->sendTransactional(
            recipients: 'client@example.com',
            subject: 'Publicité validée',
            greeting: 'Bonjour',
            lines: ['Votre publicité a été validée.'],
            action: [
                'label' => 'Payer',
                'url' => 'https://verga.test/payer',
            ],
        );

        Mail::assertSent(TransactionalMail::class, function (TransactionalMail $mailable): bool {
            return $mailable->hasTo('client@example.com')
                && $mailable->mailSubject === 'Publicité validée'
                && $mailable->greeting === 'Bonjour'
                && $mailable->lines === ['Votre publicité a été validée.']
                && $mailable->action === [
                    'label' => 'Payer',
                    'url' => 'https://verga.test/payer',
                ];
        });
    }

    public function test_queue_transactional_mail(): void
    {
        Mail::fake();

        app(MailService::class)->queueTransactional(
            recipients: ['a@example.com', 'b@example.com'],
            subject: 'Notification',
            greeting: 'Bonjour',
        );

        Mail::assertQueued(TransactionalMail::class, 1);
    }

    public function test_rejects_empty_recipients(): void
    {
        Mail::fake();

        $this->expectException(InvalidArgumentException::class);

        app(MailService::class)->sendTransactional(
            recipients: '',
            subject: 'Test',
            greeting: 'Bonjour',
        );
    }
}
