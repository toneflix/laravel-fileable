<?php
use Illuminate\Support\Facades\Route;
use ToneflixCode\LaravelFileable\Media;

Route::get(config('toneflix-fileable.file_route_secure', 'load/images/{file}'), function ($file) {
    return (new Media)->privateFile($file);
})->middleware(config('toneflix-fileable.file_route_secure_middleware', []) ?? [])->name('fileable.secure.file');

Route::get(config('toneflix-fileable.file_route_open', 'open/images/{file}'), function ($file) {
    return (new Media)->privateFile($file);
})->name('fileable.open.file');