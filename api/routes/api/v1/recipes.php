<?php

use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::controller(RecipeController::class)
    ->middleware('save-token')
    ->group(function () {
        Route::middleware(['auth', 'verified'])->group(function () {
            Route::delete('/trash/{recipes}', 'forceDestroy')
                ->name('forceDestroy')
                ->withTrashed();
            Route::put('/trash/{recipes}', 'restore')
                ->name('restore')
                ->withTrashed();
        });

        Route::get('/', 'index')->name('index');
        Route::get('/{recipe}', 'show')->name('show');
    });

Route::middleware(['auth', 'verified'])
    ->post('/{recipe}/ingredients', [IngredientController::class, 'store'])
    ->name('ingredients.store');
