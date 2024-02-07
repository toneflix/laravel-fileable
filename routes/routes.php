<?php

use Illuminate\Support\Facades\Route;
use ToneflixCode\LaravelFileable\Media;

Route::get(config('toneflix-fileable.file_route_secure', 'load/images/{file}'), function ($file) {
    return (new Media())->privateFile($file);
})->middleware(config('toneflix-fileable.file_route_secure_middleware', []) ?? [])->name('fileable.secure.file');

Route::get(config('toneflix-fileable.file_route_open', 'load/images/{file}'), function ($file) {
    return (new Media())->privateFile($file);
})->name('fileable.open.file');

Route::get(config('toneflix-fileable.responsive_image_route', 'images/responsive/{size}/{file}'),
function (string $size, string $file) {
    return (new Media())->resizeResponse($file, $size);
})->name('imagecache');
