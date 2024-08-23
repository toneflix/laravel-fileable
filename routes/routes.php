<?php

use Illuminate\Support\Facades\Route;
use ToneflixCode\LaravelFileable\Controllers\FileController;

// The public private secure generator route
Route::get(config('toneflix-fileable.file_route_secure', 'load/images/{file}'), [FileController::class, 'show'])
    ->middleware(config('toneflix-fileable.file_route_secure_middleware', []) ?? [])
    ->name('fileable.secure.file');

// The public image generator route
Route::get(config('toneflix-fileable.file_route_open', 'load/images/{file}'), [FileController::class, 'show'])
    ->name('fileable.open.file');

// The responsive images route
Route::get(
    config('toneflix-fileable.responsive_image_route', 'images/responsive/{size}/{file}'),
    [FileController::class, 'showResponsive']
)->name('imagecache');
