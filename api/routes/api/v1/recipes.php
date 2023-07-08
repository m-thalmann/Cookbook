<?php

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeImageController;
use Illuminate\Support\Facades\Route;

Route::controller(RecipeController::class)
    ->middleware('auth.optional')
    ->group(function () {
        Route::get('/shared/{shareUuid}', 'showShared')->name('shared.show');

        Route::get('/', 'index')->name('index');
        Route::get('/{recipe}', 'show')->name('show');
    });

Route::get('/{recipe}/images', [RecipeImageController::class, 'index'])
    ->middleware('auth.optional')
    ->name('images.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/{recipe}/ingredients', [
        IngredientController::class,
        'store',
    ])->name('ingredients.store');

    Route::post('/{recipe}/images', [
        RecipeImageController::class,
        'store',
    ])->name('images.store');
});
