<?php

use App\Http\Controllers\Cookbook\CookbookCategoryController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeCategoryController;
use App\Http\Controllers\Cookbook\CookbookController;
use App\Http\Controllers\Cookbook\CookbookRecipeController;
use App\Http\Controllers\Cookbook\CookbookUserController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

$routeFiles = ['auth', 'users', 'recipes', 'recipe-images'];

Route::get(
    '/',
    fn() => JsonResource::make([
        'name' => config('app.name') . ' API',
        'version' => 1,
    ])
)->name('index');

foreach ($routeFiles as $name) {
    Route::prefix($name)
        ->as("$name.")
        ->group(base_path("routes/api/v1/$name.php"));
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::apiResource('users', UserController::class);

    Route::apiResource('recipes', RecipeController::class)->except([
        'index',
        'show',
    ]);

    Route::apiResource('ingredients', IngredientController::class)->only([
        'index',
        'update',
        'destroy',
    ]);

    Route::apiResource('cookbooks', CookbookController::class)->except([
        'show',
    ]);

    Route::apiResource(
        'cookbooks.recipes',
        CookbookRecipeController::class
    )->only(['index']);

    Route::apiResource('cookbooks.users', CookbookUserController::class)
        ->except(['show'])
        ->scoped(['user' => 'id']);

    Route::apiResource(
        'cookbooks.categories',
        CookbookCategoryController::class
    )->only(['index']);
});

Route::apiResource('categories', RecipeCategoryController::class)->only([
    'index',
]);
