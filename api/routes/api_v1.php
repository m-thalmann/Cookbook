<?php

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeCategoryController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

const ROUTE_FILES = ['auth', 'recipes', 'ingredients', 'recipe-images'];

Route::get(
    '/',
    fn() => JsonResource::make([
        'name' => config('app.name') . ' API',
        'version' => 1,
    ])
)->name('index');

foreach (ROUTE_FILES as $name) {
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
});
