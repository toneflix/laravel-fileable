<?php
use Illuminate\Support\Facades\Route;
use ToneflixCode\LaravelFileable\Media;

Route::get(config('toneflix-fileable.secure_file_route', 'load/images/{file}'), function ($file) {
    return (new Media)->privateFile($file);
})->middleware(config('toneflix-fileable.secure_file_middleware', []) ?? [])->name('fileable.secure.file');