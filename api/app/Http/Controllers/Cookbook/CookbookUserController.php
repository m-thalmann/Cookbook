<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Cookbook;
use App\Models\User;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\Cookbooks\IndexCookbookUsersParameters;
use App\OpenApi\RequestBodies\Cookbooks\CreateCookbookUserRequestBody;
use App\OpenApi\RequestBodies\Cookbooks\UpdateCookbookUserRequestBody;
use App\OpenApi\Responses\ConflictResponse;
use App\OpenApi\Responses\Cookbooks\CookbookUserCreatedResponse;
use App\OpenApi\Responses\Cookbooks\CookbookUserIndexResponse;
use App\OpenApi\Responses\Cookbooks\CookbookUserShowResponse;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CookbookUserController extends Controller {
    /**
     * Lists all users in the cookbook
     *
     * @param Cookbook $cookbook The cookbook's id
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks/Users'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexCookbookUsersParameters::class)]
    #[
        OpenApi\Response(
            factory: CookbookUserIndexResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request, Cookbook $cookbook) {
        $this->authorizeAnonymously('update', $cookbook);

        return response()->pagination(
            fn($perPage) => $cookbook
                ->users()
                ->organized($request)
                ->paginate($perPage)
        );
    }

    /**
     * Adds a user to the cookbook
     *
     * @param Cookbook $cookbook The cookbook's id
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks/Users'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\RequestBody(factory: CreateCookbookUserRequestBody::class)]
    #[
        OpenApi\Response(
            factory: CookbookUserCreatedResponse::class,
            statusCode: 201
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
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
    public function store(Request $request, Cookbook $cookbook) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $cookbook);

        $data = $request->validate(
            [
                'user_id' => [
                    'bail',
                    'required',
                    'exists:App\Models\User,id',
                    Rule::unique('cookbook_user', 'user_id')->where(
                        fn($query) => $query->where(
                            'cookbook_id',
                            $cookbook->id
                        )
                    ),
                ],
                'is_admin' => ['required', 'boolean'],
            ],
            [
                'user_id.unique' => __('messages.user_already_in_cookbook'),
            ]
        );

        $cookbook->users()->attach($data['user_id'], [
            'is_admin' => $data['is_admin'],
        ]);

        $cookbookUser = $cookbook
            ->users()
            ->where('user_id', $data['user_id'])
            ->first();

        return JsonResource::make($cookbookUser)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Updates the given cookbook user
     *
     * If the user is trying to update itself the request fails with a forbidden error.
     *
     * @param Cookbook $cookbook The cookbook's id
     * @param User $user The user's id
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks/Users'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\RequestBody(factory: UpdateCookbookUserRequestBody::class)]
    #[
        OpenApi\Response(
            factory: CookbookUserShowResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
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
    public function update(Request $request, Cookbook $cookbook, User $user) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $cookbook);

        if ($user->id === auth()->id()) {
            throw new AuthorizationException(__('messages.cant_update_self'));
        }

        $data = $request->validate([
            'is_admin' => ['boolean'],
        ]);

        $cookbook->users()->updateExistingPivot($user->id, $data);

        $cookbookUser = $cookbook
            ->users()
            ->where('user_id', $user->id)
            ->first();

        return JsonResource::make($cookbookUser);
    }

    /**
     * Removes the user from the given cookbook
     *
     * After removing the user from the cookbook all recipes the user owns that
     * are part of the cookbook are removed from it.
     *
     * If the user is the last admin of the cookbook the request fails with a conflict error.
     *
     * @param Cookbook $cookbook The cookbook's id
     * @param int $user The id of the user that should be removed
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks/Users'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[OpenApi\Response(factory: ConflictResponse::class, statusCode: 409)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(Cookbook $cookbook, int $user) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $cookbook);

        if (
            $cookbook
                ->users()
                ->whereNot('users.id', $user)
                ->where('cookbook_user.is_admin', true)
                ->count() === 0
        ) {
            throw new ConflictHttpException(
                __('messages.cookbooks.cant_delete_last_admin_user')
            );
        }

        $cookbook->users()->detach($user);

        Recipe::query()
            ->where('user_id', $user)
            ->where('cookbook_id', $cookbook->id)
            ->update(['cookbook_id' => null]);

        return response()->noContent();
    }
}
