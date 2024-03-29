<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\OpenApi\Parameters\Categories\IndexCategoriesParameters;
use App\OpenApi\Responses\Categories\CategoryIndexResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class RecipeCategoryController extends Controller {
    /**
     * Lists all (distinct) categories (visible to the user) in ascending order
     *
     * If authentication is provided it will show all categories for recipes owned by the user.
     * If furthermore the `all` parameter is set, all categories of visible recipes for the user are returned (admin can see any recipe).
     *
     * If no authentication is provided the categories of all public recipes are returned (independent of the `all` parameter).
     *
     * The `sort` parameter can be used to sort the categories by the amount of recipes in the category.
     * If it is not set the categories are default sorted by their name (asc).
     */
    #[
        OpenApi\Operation(
            tags: ['Categories'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexCategoriesParameters::class)]
    #[OpenApi\Response(factory: CategoryIndexResponse::class, statusCode: 200)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request) {
        $all = $request->exists('all');
        $sort = $request->input('sort', null);

        $categories = Recipe::query()
            ->forUser(authUser(), $all)
            ->whereNotNull('category')
            ->categories($sort)
            ->get()
            ->pluck('category');

        return JsonResource::make($categories);
    }
}
