<?php

namespace App\Console\Commands;

use App\Services\MailService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('mail:test {email : Adresse de destination}')]
#[Description('Envoie un e-mail de test via la configuration SMTP active')]
class MailSendTestCommand extends Command
{
    public function handle(MailService $mail): int
    {
        $recipient = (string) $this->argument('email');

        $mail->sendTransactional(
            recipients: $recipient,
            subject: 'Test SMTP — '.config('app.name'),
            greeting: 'Configuration SMTP opérationnelle',
            lines: [
                'Cet e-mail confirme que l\'envoi via '.config('mail.default').' fonctionne correctement.',
                'Expéditeur : '.config('mail.from.address'),
            ],
            action: [
                'label' => 'Ouvrir '.config('app.name'),
                'url' => config('app.url'),
            ],
        );

        $this->components->info("E-mail de test envoyé à {$recipient}.");

        return self::SUCCESS;
    }
}
