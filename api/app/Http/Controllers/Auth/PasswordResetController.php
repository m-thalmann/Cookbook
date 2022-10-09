<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\HttpException;
use App\Http\Controllers\Controller;
use App\OpenApi\RequestBodies\Auth\ResetPasswordRequestBody;
use App\OpenApi\RequestBodies\Auth\SendResetPasswordRequestBody;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class PasswordResetController extends Controller {
    /**
     * Resets the user's password
     */
    #[OpenApi\Operation(tags: ['Auth'])]
    #[OpenApi\RequestBody(factory: ResetPasswordRequestBody::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function reset(Request $request) {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', PasswordRule::default()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
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
            throw new AuthorizationException();
        }
    }

    /**
     * Requests a password-reset-email
     */
    #[OpenApi\Operation(tags: ['Auth'])]
    #[OpenApi\RequestBody(factory: SendResetPasswordRequestBody::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
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
