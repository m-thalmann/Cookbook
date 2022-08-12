<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(UserController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/search/email/{email}', 'showByEmail')->name('showByEmail');
    });
