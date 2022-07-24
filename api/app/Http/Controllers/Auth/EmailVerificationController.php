<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller {
    public function verify(EmailVerificationRequest $request) {
        $this->checkEnabled();

        $request->fulfill();

        return response()->noContent();
    }

    public function sendVerificationEmail() {
        $this->checkEnabled();

        authUser()->sendEmailVerificationNotification();

        return response()->noContent();
    }

    private function checkEnabled() {
        if (!config('app.email_verification_enabled')) {
            throw new AuthorizationException(
                __('auth.email_verification_disabled')
            );
        }
    }
}
