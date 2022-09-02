<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\ServiceProvider;

use ToneflixCode\LaravelFileable\Intervention\Media1080;
use ToneflixCode\LaravelFileable\Intervention\Media431;
use ToneflixCode\LaravelFileable\Intervention\Media694;
use ToneflixCode\LaravelFileable\Intervention\Media720;

class FileableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        config([
            'filesystems' => 
                collect(config('filesystems'))
                ->merge(config('toneflix-fileable.filesystems'))
                ->toArray(),
        ]);

        config([
            'imagecache' => 
                collect(config('imagecache'))
                ->merge([
                    'paths' => collect('toneflix-fileable.filesystems.links')->values(),
                ])->toArray(),
        ]);

        /*
         * Optional methods to load your package assets
         */
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('toneflix-fileable.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'toneflix-fileable');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-fileable', function () {
            return new Media;
        });
    }
}