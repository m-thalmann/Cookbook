<?php

namespace App\Api\V1\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller {
    public function verify(EmailVerificationRequest $request) {
        $request->fulfill();

        return response()->noContent();
    }

    public function sendVerificationEmail() {
        authUser()->sendEmailVerificationNotification();

        return response()->noContent();
    }
}
