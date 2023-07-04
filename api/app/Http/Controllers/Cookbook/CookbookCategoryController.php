<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Cookbook;
use App\OpenApi\Parameters\Cookbooks\IndexCookbookCategoriesParameters;
use App\OpenApi\Responses\Categories\CategoryIndexResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CookbookCategoryController extends Controller {
    /**
     * Lists all (distinct) categories inside the cookbook in ascending order
     *
     * The `sort` parameter can be used to sort the categories by the amount of recipes in the category.
     * If it is not set the categories are default sorted by their name (asc).
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexCookbookCategoriesParameters::class)]
    #[OpenApi\Response(factory: CategoryIndexResponse::class, statusCode: 200)]
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

        $sort = $request->input('sort', null);

        $categories = $cookbook
            ->recipes()
            ->whereNotNull('category')
            ->categories($sort)
            ->get()
            ->pluck('category');

        return JsonResource::make($categories);
    }
}
