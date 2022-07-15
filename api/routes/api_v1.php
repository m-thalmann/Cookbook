<?php

use App\Api\V1\Controllers\UserController;
use App\Api\V1\Resources\UserResource;
use App\Helpers\Builder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    fn() => JsonResource::make([
        'name' => config('app.name') . ' API',
        'version' => 1,
    ])
);

Route::prefix('auth')->group(base_path('routes/api/v1/auth.php'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::apiResource('users', UserController::class);
});
