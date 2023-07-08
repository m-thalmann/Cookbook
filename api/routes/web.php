<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app')->name('app');

Route::view('/api/docs', 'swagger')->name('docs');
