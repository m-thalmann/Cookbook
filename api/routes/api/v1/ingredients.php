<?php

use App\Http\Controllers\IngredientController;
use Illuminate\Support\Facades\Route;

Route::controller(IngredientController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });
