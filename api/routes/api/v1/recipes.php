<?php

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeImageController;
use Illuminate\Support\Facades\Route;

Route::controller(RecipeController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/trash', 'indexTrash')->name('trash.index');
        Route::delete('/trash', 'truncateTrash')->name('trash.truncate');
        Route::delete('/trash/{recipe}', 'forceDestroy')
            ->name('trash.delete')
            ->withTrashed();
        Route::put('/trash/{recipe}', 'restore')
            ->name('trash.restore')
            ->withTrashed();
    });

Route::controller(RecipeController::class)
    ->middleware('save-token')
    ->group(function () {
        Route::get('/shared/{recipeShareUuid}', 'showShared')->name(
            'shared.show'
        );

        Route::get('/', 'index')->name('index');
        Route::get('/{recipe}', 'show')->name('show');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/{recipe}/ingredients', [
        IngredientController::class,
        'store',
    ])->name('ingredients.store');

    Route::controller(RecipeImageController::class)->group(function () {
        Route::get('/{recipe}/images', 'index')->name('images.index');
        Route::post('/{recipe}/images', 'store')->name('images.store');
    });
});
