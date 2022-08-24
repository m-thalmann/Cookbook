<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends ResetPasswordBase {
    protected function buildMailMessage($url) {
        return (new MailMessage())
            ->subject(__('notifications.reset_password.subject'))
            ->line(__('notifications.reset_password.line1'))
            ->action(__('notifications.reset_password.action_text'), $url)
            ->line(
                __('notifications.reset_password.line2', [
                    'count' => config(
                        'auth.passwords.' .
                            config('auth.defaults.passwords') .
                            '.expire'
                    ),
                ])
            )
            ->line(__('notifications.reset_password.bottom_line'));
    }

    protected function resetUrl($notifiable) {
        return config('app.frontend_url') .
            "/password-reset/{$notifiable->getEmailForPasswordReset()}/{$this->token}";
    }
}

