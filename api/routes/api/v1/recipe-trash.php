<?php

use App\Http\Controllers\RecipeTrashController;
use Illuminate\Support\Facades\Route;

Route::controller(RecipeTrashController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::delete('/', 'truncate')->name('truncate');
    });
