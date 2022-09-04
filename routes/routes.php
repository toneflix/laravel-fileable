<?php
use Illuminate\Support\Facades\Route;
use ToneflixCode\LaravelFileable\Media;

Route::get(config('toneflix-fileable.secure_file_route', 'load/images/{file}'), function ($file) {
    return (new Media)->privateFile($file);
})->middleware(config('secure_image_middleware') ? ['window_auth'] : [])->name('fileable.secure.file');