<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\ServiceProvider;

class FileableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');

        config([
            'filesystems.links' => collect(config('filesystems.links'))
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
                    '431' => [431, 767],
                    '694' => [431, 767],
                    '720' => [431, 767],
                    '1080' => [431, 767],
                ]),
        ]);

        /*
         * Optional methods to load your package assets
         */
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('toneflix-fileable.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'toneflix-fileable');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-fileable', function () {
            return new Media();
        });
    }
}