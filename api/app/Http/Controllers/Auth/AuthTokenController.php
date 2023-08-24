<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\AuthTokenResource;
use App\Http\Controllers\Controller;
use App\Models\AuthToken;
use App\OpenApi\Parameters\Auth\AuthTokenParameters;
use App\OpenApi\Parameters\Auth\ShowAuthTokensParameters;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Responses\Auth\Tokens\TokenIndexResponse;
use App\OpenApi\Responses\Auth\Tokens\TokenShowResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use TokenAuth\Enums\TokenType;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class AuthTokenController extends Controller {
    /**
     * Returns the active refresh tokens for the user (only refresh tokens)
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: AuthTokenParameters::class)]
    #[OpenApi\Response(factory: TokenIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index() {
        $this->verifyNoDemo();

        return response()->pagination(
            fn($perPage) => AuthTokenResource::collection(
                authUser()
                    ->tokens()
                    ->active()
                    ->type(TokenType::REFRESH)
                    ->paginate($perPage)
            )
        );
    }

    /**
     * Returns the searched auth token
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: ShowAuthTokensParameters::class)]
    #[OpenApi\Response(factory: TokenShowResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function show(AuthToken $authToken) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('view', $authToken);

        return AuthTokenResource::make($authToken);
    }

    /**
     * Returns all tokens within the given group
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: AuthTokenParameters::class)]
    #[OpenApi\Response(factory: TokenIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function indexGroup(int $groupId) {
        $this->verifyNoDemo();

        return response()->pagination(
            fn($perPage) => AuthTokenResource::collection(
                authUser()
                    ->tokens()
                    ->where('group_id', $groupId)
                    ->paginate($perPage)
            )
        );
    }

    /**
     * Deletes the given token and all tokens from the same group
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: ShowAuthTokensParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(AuthToken $authToken) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('delete', $authToken);

        if ($authToken->getGroupId() === null) {
            $authToken->delete();
        } else {
            AuthToken::deleteTokensFromGroup($authToken->getGroupId());
        }

        return response()->noContent();
    }

    /**
     * Deletes all tokens for the authenticated user
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function truncate() {
        $this->verifyNoDemo();

        authUser()
            ->tokens()
            ->delete();

        return response()->noContent();
    }
}
