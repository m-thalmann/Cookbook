<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Cookbook;
use App\OpenApi\Parameters\Cookbooks\IndexCookbookRecipesParameters;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\Recipes\RecipeIndexResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CookbookRecipeController extends Controller {
    /**
     * Lists all recipes in the cookbook
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexCookbookRecipesParameters::class)]
    #[OpenApi\Response(factory: RecipeIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request, Cookbook $cookbook) {
        $this->authorizeAnonymously('view', $cookbook);

        $recipes = $cookbook
            ->recipes()
            ->with(['user', 'thumbnail', 'cookbook'])
            ->organized($request);

        return response()->pagination(
            fn($perPage) => RecipeResource::collection(
                $recipes->paginate($perPage)
            )
        );
    }
}
