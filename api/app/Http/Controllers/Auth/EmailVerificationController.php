<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\OpenApi\Parameters\Auth\EmailVerificationParameters;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class EmailVerificationController extends Controller {
    /**
     * Verifies the email address for the authenticated user
     */
    #[OpenApi\Operation(tags: ['Auth'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Parameters(factory: EmailVerificationParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function verify(EmailVerificationRequest $request) {
        $this->checkEnabled();

        $request->fulfill();

        return response()->noContent();
    }

    /**
     * Resends the email-verification email for the authenticated user
     */
    #[OpenApi\Operation(tags: ['Auth'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
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
