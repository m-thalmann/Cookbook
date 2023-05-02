<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Cookbook;
use App\OpenApi\Responses\Categories\CategoryIndexResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CookbookCategoryController extends Controller {
    /**
     * Lists all (distinct) categories inside the cookbook in ascending order
     *
     * @param Cookbook $cookbook The cookbook's id
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Response(factory: CategoryIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Cookbook $cookbook) {
        $this->authorizeAnonymously('view', $cookbook);

        $categories = $cookbook
            ->recipes()
            ->whereNotNull('category')
            ->orderBy('category', 'asc')
            ->select('category')
            ->distinct()
            ->get()
            ->pluck('category');

        return JsonResource::make($categories);
    }
}
