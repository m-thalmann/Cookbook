<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\HttpException;
use App\Exceptions\UnauthorizedHttpException;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\OpenApi\RequestBodies\Auth\LoginRequestBody;
use App\OpenApi\RequestBodies\Auth\SignUpRequestBody;
use App\OpenApi\Responses\Auth\LoginSuccessResponse;
use App\OpenApi\Responses\Auth\RefreshTokenResponse;
use App\OpenApi\Responses\Auth\SignUpSuccessResponse;
use App\OpenApi\Responses\Auth\UserResponse;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use App\Services\HCaptchaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use TokenAuth\TokenAuth;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class AuthenticationController extends Controller {
    const EMAIL_UNVERIFIED_HEADER = 'X-Unverified';

    /**
     * Performs a login for the user
     *
     * Uses the credentials from the request and tries to login the user.
     * If the authentication succeeds the user and tokens are returned.
     */
    #[OpenApi\Operation(tags: ['Auth'])]
    #[OpenApi\RequestBody(factory: LoginRequestBody::class)]
    #[OpenApi\Response(factory: LoginSuccessResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
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
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw UnauthorizedHttpException::credentials(__('auth.failed'));
        }

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            config('auth.token_names.refresh'),
            config('auth.token_names.access'),
            save: false
        );

        $refreshToken->token->setRequestAttributes($request)->save();
        $accessToken->token->save();

        $response = JsonResource::make([
            'user' => UserResource::make($user),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ])->response();

        if (!$user->hasVerifiedEmail()) {
            $response->header(self::EMAIL_UNVERIFIED_HEADER, 'true');
        }

        return $response;
    }

    /**
     * Creates an account for a new user
     *
     * Uses the data supplied to create a new user
     */
    #[OpenApi\Operation(tags: ['Auth'])]
    #[OpenApi\RequestBody(factory: SignUpRequestBody::class)]
    #[OpenApi\Response(factory: SignUpSuccessResponse::class, statusCode: 200)]
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
    public function signUp(Request $request) {
        if (!config('app.sign_up_enabled')) {
            throw new HttpException(405, __('auth.sign_up_disabled'));
        }

        $this->verifyNoDemo();

        if (config('services.hcaptcha.enabled')) {
            /**
             * @var HCaptchaService
             */
            $hcaptchaService = app(HCaptchaService::class);

            $hcaptchaToken = $request->get('hcaptcha_token');
            if (!$hcaptchaToken || !$hcaptchaService->verify($hcaptchaToken)) {
                throw new AuthorizationException(
                    __('auth.hcpatcha_token_invalid')
                );
            }
        }

        $data = $request->validate([
            'name' => ['required', 'filled', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => ['required', Password::default()],
            'language_code' => ['nullable', 'min:2', 'max:2'],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        event(new Registered($user));

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            config('auth.token_names.refresh'),
            config('auth.token_names.access'),
            save: false
        );

        $refreshToken->token->setRequestAttributes($request)->save();
        $accessToken->token->save();

        $response = JsonResource::make([
            'user' => UserResource::make($user),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ])
            ->response()
            ->setStatusCode(201);

        if (!$user->hasVerifiedEmail()) {
            $response->header(self::EMAIL_UNVERIFIED_HEADER, 'true');
        }

        return $response;
    }

    /**
     * Refreshes the token
     *
     * Uses the supplied refresh-token to generate a new access/refresh-token-pair
     */
    #[OpenApi\Operation(tags: ['Auth'], security: 'RefreshTokenSecurityScheme')]
    #[OpenApi\Response(factory: RefreshTokenResponse::class, statusCode: 201)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function refresh(Request $request) {
        [$refreshToken, $accessToken] = TokenAuth::rotateRefreshToken(
            config('auth.token_names.access'),
            save: false
        );

        $refreshToken->token->setRequestAttributes($request)->save();
        $accessToken->token->save();

        // since is not saved in "rotateRefreshToken" function
        authUser()
            ->currentToken()
            ->save();

        return JsonResource::make([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Performs a logout for the user
     *
     * Deletes all access and refresh tokens for the session (from the same group)
     */
    #[OpenApi\Operation(tags: ['Auth'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 201)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function logout() {
        authUser()
            ->currentToken()
            ->deleteAllTokensFromSameGroup();

        return response()->noContent();
    }

    /**
     * Returns the authenticated user
     */
    #[OpenApi\Operation(tags: ['Auth'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Response(factory: UserResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function viewAuthUser() {
        $user = authUser();

        $response = UserResource::make($user)->response();

        if (!$user->hasVerifiedEmail()) {
            $response->header(self::EMAIL_UNVERIFIED_HEADER, 'true');
        }

        return $response;
    }
}
