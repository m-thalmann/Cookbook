<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\Recipes\IndexRecipesTrashParameters;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\Recipes\RecipeIndexResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class RecipeTrashController extends Controller {
    /**
     * Lists all recipes in the authenticated user's trash
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Trash'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexRecipesTrashParameters::class)]
    #[OpenApi\Response(factory: RecipeIndexResponse::class, statusCode: 200)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request) {
        $recipes = authUser()
            ->recipes()
            ->with([
                'user',
                'thumbnail',
                'cookbook' => function ($query) {
                    $query->forUser(authUser())->exists();
                },
            ])
            ->onlyTrashed()
            ->filter($request)
            ->search($request)
            ->sort($request, ['deleted_at']);

        return response()->pagination(
            fn($perPage) => RecipeResource::collection(
                $recipes->paginate($perPage)
            )
        );
    }

    /**
     * Restores the recipe from the trash
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Trash'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function update(Recipe $recipe) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('restore', $recipe);

        $recipe->restore();

        return response()->noContent();
    }

    /**
     * Deletes the recipe
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Trash'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(Recipe $recipe) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('forceDelete', $recipe);

        $recipe->forceDelete();

        return response()->noContent();
    }

    /**
     * Deletes all recipes in the trash for the authenticated user
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Trash'],
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

        $query = authUser()
            ->recipes()
            ->onlyTrashed();

        Recipe::deleteImageFiles($query);

        $query->forceDelete();

        return response()->noContent();
    }
}

