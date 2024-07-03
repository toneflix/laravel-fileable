<?php

namespace ToneflixCode\LaravelFileable\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use ToneflixCode\LaravelFileable\FileableServiceProvider;
use ToneflixCode\LaravelFileable\Tests\Database\Factories\UserFactory;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected $factories = [
        UserFactory::class,
    ];

    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('app.faker_locale', 'en_NG');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'ToneflixCode\\LaravelFileable\\Tests\\Database\\Factories\\'.
                class_basename(
                    $modelName
                ).'Factory'
        );
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            FileableServiceProvider::class,
        ];
    }
}
