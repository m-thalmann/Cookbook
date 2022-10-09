<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\AuthTokenResource;
use App\Http\Controllers\Controller;
use App\Models\AuthToken;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Responses\Auth\Tokens\TokenIndexResponse;
use App\OpenApi\Responses\Auth\Tokens\TokenShowResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class AuthTokenController extends Controller {
    /**
     * Returns the active access tokens for the user (only access tokens)
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: PaginationParameters::class)]
    #[OpenApi\Response(factory: TokenIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index() {
        return response()->pagination(
            fn($perPage) => AuthTokenResource::collection(
                authUser()
                    ->tokens()
                    ->active()
                    ->accessTokens()
                    ->paginate($perPage)
            )
        );
    }

    /**
     * Returns the searched auth token
     *
     * @param AuthToken $authToken The id of the searched auth-token
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
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
        $this->authorizeAnonymously('view', $authToken);

        return AuthTokenResource::make($authToken);
    }

    /**
     * Returns all tokens within the given group
     *
     * @param int $groupId The id of the group
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: PaginationParameters::class)]
    #[OpenApi\Response(factory: TokenIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function indexGroup(int $groupId) {
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
     *
     * @param AuthToken $authToken The id of the searched auth-token
     */
    #[
        OpenApi\Operation(
            tags: ['Auth/Tokens'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
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
        $this->authorizeAnonymously('delete', $authToken);

        $authToken->deleteAllTokensFromSameGroup();

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
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function truncate() {
        authUser()
            ->tokens()
            ->delete();

        return response()->noContent();
    }
}
