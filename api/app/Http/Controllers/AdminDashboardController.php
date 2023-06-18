<?php

namespace App\Http\Controllers;

use App\Models\Cookbook;
use App\Models\Recipe;
use App\Models\RecipeImage;
use App\Models\User;
use App\OpenApi\Responses\AdminDashboardResponse;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class AdminDashboardController extends Controller {
    /**
     * Returns the data for the admin dashboard
     */
    #[OpenApi\Operation(tags: ['Admin'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Response(factory: AdminDashboardResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function __invoke() {
        $this->authorize('isAdmin', User::class);

        return JsonResource::make([
            'api' => $this->getApiData(),
            'users' => $this->getUsersData(),
            'recipes' => $this->getRecipesData(),
            'cookbooks' => $this->getCookbooksData(),
            'recipe_images' => $this->getRecipeImagesData(),
        ]);
    }

    protected function getApiData() {
        return [
            'version' => config('app.api_version'),
            'environment' => config('app.env'),
        ];
    }

    protected function getUsersData() {
        $amountUsers = User::count();

        $amountAdminUsers = User::query()
            ->isAdmin()
            ->count();

        return [
            'admin_amount' => $amountAdminUsers,
            'total_amount' => $amountUsers,
        ];
    }

    protected function getRecipesData() {
        $amountPublicRecipes = Recipe::query()
            ->isPublic()
            ->count();
        $amountPrivateRecipes = Recipe::query()
            ->isPublic(false)
            ->count();

        return [
            'public_amount' => $amountPublicRecipes,
            'private_amount' => $amountPrivateRecipes,
            'total_amount' => $amountPublicRecipes + $amountPrivateRecipes,
        ];
    }

    protected function getCookbooksData() {
        $amountCookbooks = Cookbook::count();

        return [
            'total_amount' => $amountCookbooks,
        ];
    }

    protected function getRecipeImagesData() {
        $images = Storage::disk('public')->allFiles(
            RecipeImage::IMAGE_DIRECTORY
        );

        $storageSize = collect($images)->reduce(function ($size, $image) {
            return $size + Storage::disk('public')->size($image);
        }, 0);

        return [
            'total_amount' => count($images),
            'storage_size' => $storageSize,
        ];
    }
}

