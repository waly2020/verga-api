<?php

namespace App\Notifications\Auth;

use App\Notifications\Concerns\BuildsFrenchResetPasswordMail;
use App\Support\FrontendPasswordResetUrl;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ClientResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use BuildsFrenchResetPasswordMail;
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return $this->buildFrenchResetPasswordMail($this->resetUrl($notifiable));
    }

    protected function resetUrl(mixed $notifiable): string
    {
        return FrontendPasswordResetUrl::client(
            $this->token,
            $notifiable->getEmailForPasswordReset(),
        );
    }
}
