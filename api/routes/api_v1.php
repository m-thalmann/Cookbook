<?php

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeCategoryController;
use App\Http\Controllers\RecipeCollectionController;
use App\Http\Controllers\RecipeCollectionRecipeController;
use App\Http\Controllers\RecipeCollectionUserController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\RecipeCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

$routeFiles = ['auth', 'users', 'recipes', 'ingredients', 'recipe-images'];

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
        'update',
        'destroy',
    ]);
    Route::apiResource('categories', RecipeCategoryController::class)->only([
        'index',
    ]);
    Route::apiResource(
        'collections',
        RecipeCollectionController::class
    )->except(['show']);
    Route::apiResource(
        'collections.recipes',
        RecipeCollectionRecipeController::class
    )->only(['index']);
    Route::apiResource(
        'collections.users',
        RecipeCollectionUserController::class
    )
        ->except(['show'])
        ->scoped(['user' => 'id']);
});
