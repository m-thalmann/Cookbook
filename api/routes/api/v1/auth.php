<?php

use App\Api\V1\Controllers\Auth\AuthenticationController;
use App\Api\V1\Controllers\Auth\AuthTokenController;
use App\Api\V1\Controllers\Auth\EmailVerificationController;
use App\Api\V1\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthenticationController::class)->group(function () {
    Route::get('/', 'viewAuthUser')->middleware('auth');

    Route::post('/login', 'login')->middleware(['throttle:6,1']);
    Route::post('/register', 'register')->middleware(['throttle:6,1']);
    Route::post('/logout', 'logout')->middleware('auth');

    Route::post('/refresh', 'refresh')->middleware([
        'auth-no-save:token-refresh',
        'verified',
    ]);
});

Route::get(
    '/registration-enabled',
    fn() => ['data' => config('app.registration_enabled')]
);

if (config('app.email_verification_enabled')) {
    Route::controller(EmailVerificationController::class)
        ->middleware('auth')
        ->group(function () {
            Route::post('/email-verification/verify/{id}/{hash}', 'verify')
                ->middleware('signed')
                ->name('auth.email.verify');
            Route::post('/email-verification/resend', 'sendVerificationEmail');
        });
}

Route::controller(PasswordResetController::class)
    ->middleware(['throttle:6,1'])
    ->group(function () {
        Route::post('/reset-password', 'reset');
        Route::post('/reset-password/send', 'sendResetEmail');
    });

Route::controller(AuthTokenController::class)
    ->middleware(['auth', 'verified'])
    ->prefix('tokens')
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/groups/{groupId}', 'indexGroup');
        Route::get('/{authToken}', 'show');
        Route::delete('/{authToken}', 'destroy');
        Route::delete('/', 'truncate');
    });
