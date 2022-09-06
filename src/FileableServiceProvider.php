<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
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
        $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');

        config([
            'filesystems.links' =>
                collect(config('filesystems.links'))
                ->merge(config('toneflix-fileable.symlinks', []))
                ->toArray(),
        ]);

        config([
            'imagecache.route' => config('imagecache.route', 'images/responsive'),
            'imagecache.paths' => collect(config('imagecache.paths'))
                ->merge(collect(config('toneflix-fileable.symlinks', []))->values())
                ->merge(collect(config('toneflix-fileable.symlinks', []))->keys())
                ->merge(Initiator::collectionPaths())
                ->unique()
                ->values()
                ->toArray(),
            'imagecache.templates' => collect(config('imagecache.templates'))
                ->union(config('toneflix-fileable.image_templates', []))
                ->union([
                    '431' => Media431::class,
                    '694' => Media694::class,
                    '720' => Media720::class,
                    '1080' => Media1080::class,
                    '1080' => Media1080::class,
                ]),
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