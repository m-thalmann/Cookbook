<?php

use Illuminate\Support\Facades\Route;
use Vyuldashev\LaravelOpenApi\Generator as OpenApiGenerator;

Route::get('/docs', function (OpenApiGenerator $generator) {
    return view('swagger', ['spec' => $generator->generate()->toJson()]);
});
