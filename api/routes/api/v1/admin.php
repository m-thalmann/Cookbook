<?php

use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AdminDashboardController::class)
    ->name('admin.dashboard')
    ->middleware(['auth', 'verified']);
