<?php

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\AuthTokenController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthenticationController::class)->group(function () {
    Route::get('/', 'viewAuthUser')
        ->middleware('auth')
        ->name('me');

    Route::post('/login', 'login')
        ->middleware(['throttle:6,1'])
        ->name('login');
    Route::post('/register', 'register')
        ->middleware(['throttle:6,1'])
        ->name('register');
    Route::post('/logout', 'logout')
        ->middleware('auth')
        ->name('logout');

    Route::post('/refresh', 'refresh')
        ->middleware(['auth-no-save:token-refresh', 'verified'])
        ->name('refresh');
});

Route::get(
    '/registration-enabled',
    fn() => ['data' => config('app.registration_enabled')]
)->name('registration_enabled');

Route::controller(EmailVerificationController::class)
    ->middleware('auth')
    ->prefix('email-verification')
    ->as('email_verification.')
    ->group(function () {
        Route::post('/verify/{id}/{hash}', 'verify')
            ->middleware('signed')
            ->name('verify');
        Route::post('/resend', 'sendVerificationEmail')->name('send');
    });

Route::controller(PasswordResetController::class)
    ->middleware(['throttle:6,1'])
    ->prefix('reset-password')
    ->as('reset_password.')
    ->group(function () {
        Route::post('/', 'reset')->name('reset');
        Route::post('/send', 'sendResetEmail')->name('send');
    });

Route::controller(AuthTokenController::class)
    ->middleware(['auth', 'verified'])
    ->prefix('tokens')
    ->as('tokens.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/groups/{groupId}', 'indexGroup')->name('groups.view');
        Route::get('/{authToken}', 'show')->name('view');
        Route::delete('/{authToken}', 'destroy')->name('destroy');
        Route::delete('/', 'truncate')->name('truncate');
    });
