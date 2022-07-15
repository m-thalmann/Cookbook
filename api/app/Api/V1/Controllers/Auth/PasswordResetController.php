<?php

namespace App\Api\V1\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetController extends Controller {
    public function reset(Request $request) {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ]);

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->noContent();
        } else {
            $httpStatus = $status === PASSWORD::INVALID_USER ? 404 : 403;

            throw new HttpException($httpStatus, __($status));
        }
    }

    public function sendResetEmail(Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if (
            in_array($status, [
                Password::RESET_LINK_SENT,
                Password::INVALID_USER,
            ])
        ) {
            return response()->noContent();
        } else {
            throw new HttpException(500, __($status));
        }
    }
}
