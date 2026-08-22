<?php

namespace App\Notifications\Auth;

use App\Notifications\Concerns\BuildsFrenchResetPasswordMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use BuildsFrenchResetPasswordMail;
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return $this->buildFrenchResetPasswordMail($this->resetUrl($notifiable));
    }
}
