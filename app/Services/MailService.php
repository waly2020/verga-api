<?php

namespace App\Services;

use App\Mail\TransactionalMail;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class MailService
{
    /**
     * @param  string|list<string>  $recipients
     */
    public function send(MailableContract $mailable, string|array $recipients): void
    {
        Mail::to($this->normalizeRecipients($recipients))->send($mailable);
    }

    /**
     * @param  string|list<string>  $recipients
     */
    public function queue(Mailable $mailable, string|array $recipients): void
    {
        Mail::to($this->normalizeRecipients($recipients))->queue($mailable);
    }

    /**
     * E-mail transactionnel markdown (validation, notifications, etc.).
     *
     * @param  string|list<string>  $recipients
     * @param  list<string>  $lines
     * @param  array{label: string, url: string}|null  $action
     */
    public function sendTransactional(
        string|array $recipients,
        string $subject,
        string $greeting,
        array $lines = [],
        ?array $action = null,
        ?string $salutation = null,
    ): void {
        $this->send(
            new TransactionalMail($subject, $greeting, $lines, $action, $salutation),
            $recipients,
        );
    }

    /**
     * @param  string|list<string>  $recipients
     */
    public function queueTransactional(
        string|array $recipients,
        string $subject,
        string $greeting,
        array $lines = [],
        ?array $action = null,
        ?string $salutation = null,
    ): void {
        $this->queue(
            new TransactionalMail($subject, $greeting, $lines, $action, $salutation),
            $recipients,
        );
    }

    /**
     * @param  string|list<string>  $recipients
     * @return string|list<string>
     */
    private function normalizeRecipients(string|array $recipients): string|array
    {
        if (is_string($recipients)) {
            if ($recipients === '') {
                throw new InvalidArgumentException('Le destinataire ne peut pas être vide.');
            }

            return $recipients;
        }

        $filtered = array_values(array_filter($recipients, fn (string $email) => $email !== ''));

        if ($filtered === []) {
            throw new InvalidArgumentException('Au moins un destinataire est requis.');
        }

        return $filtered;
    }
}
