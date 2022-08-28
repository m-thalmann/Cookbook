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

Route::controller(RecipeController::class)->group(function () {
    Route::get('/shared/{shareUuid}', 'showShared')->name('shared.show');

    Route::get('/', 'index')->name('index');
    Route::get('/{recipe}', 'show')->name('show');
});

Route::get('/{recipe}/images', [RecipeImageController::class, 'index'])->name(
    'images.index'
);

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
