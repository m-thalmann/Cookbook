<?php

use App\Http\Controllers\Cookbook\CookbookController;
use Illuminate\Support\Facades\Route;

Route::controller(CookbookController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/editable', 'indexEditable')->name('indexEditable');
    });
