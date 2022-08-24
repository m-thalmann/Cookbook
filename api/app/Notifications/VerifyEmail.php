<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends VerifyEmailBase {
    protected function buildMailMessage($url) {
        return (new MailMessage())
            ->subject(__('notifications.verify_email.subject'))
            ->line(__('notifications.verify_email.line1'))
            ->action(__('notifications.verify_email.action_text'), $url)
            ->line(__('notifications.verify_email.bottom_line'));
    }

    protected function verificationUrl($notifiable) {
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        $apiVersion = config('app.api_version');

        list(
            'signature' => $signature,
            'expires' => $expires,
        ) = signedRouteParameters(
            "api.v{$apiVersion}.auth.email_verification.verify",
            [
                'id' => $id,
                'hash' => $hash,
            ],
            Carbon::now()->addMinutes(config('auth.verification.expire', 60))
        );

        return config('app.frontend_url') .
            "/verify-email/$id/$hash?expires=$expires&signature=$signature";
    }
}

