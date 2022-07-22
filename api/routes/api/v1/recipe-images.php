<?php

use App\Http\Controllers\RecipeImageController;
use Illuminate\Support\Facades\Route;

Route::controller(RecipeImageController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::delete('/{recipeImage}', 'destroy')->name('destroy');
    });
