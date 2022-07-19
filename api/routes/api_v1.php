<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    fn() => JsonResource::make([
        'name' => config('app.name') . ' API',
        'version' => 1,
    ])
)->name('index');

Route::prefix('auth')
    ->as('auth.')
    ->group(base_path('routes/api/v1/auth.php'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::apiResource('users', UserController::class);
});
