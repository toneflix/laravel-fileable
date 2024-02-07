<?php

namespace ToneflixCode\LaravelFileable\Tests;

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

    public function getEnvironmentSetUp($app)
    {
        loadEnv();

        config()->set('app.key', 'base64:EWcFBKBT8lKlGK8nQhTHY+wg19QlfmbhtO9Qnn3NfcA=');

        config()->set('database.default', 'testing');

        config()->set('app.faker_locale', 'en_NG');

        config()->set(
            'kudi-notification.gateway',
            $_SERVER['KUDISMS_GATEWAY'] ?? $_ENV['KUDISMS_GATEWAY'] ?? ''
        );
        config()->set(
            'kudi-notification.api_key',
            $_SERVER['KUDISMS_API_KEY'] ?? $_ENV['KUDISMS_API_KEY'] ?? ''
        );
        config()->set(
            'kudi-notification.sender_id',
            $_SERVER['KUDISMS_SENDER_ID'] ?? $_ENV['KUDISMS_SENDER_ID'] ?? ''
        );
        config()->set(
            'kudi-notification.caller_id',
            $_SERVER['KUDISMS_CALLER_ID'] ?? $_ENV['KUDISMS_CALLER_ID'] ?? config('kudi-notification.sender_id') ?? ''
        );
        config()->set(
            'kudi-notification.test_numbers',
            $_SERVER['KUDISMS_TEST_NUMBERS'] ?? $_ENV['KUDISMS_TEST_NUMBERS'] ?? ''
        );

        $migration = include __DIR__.'/database/migrations/create_users_tables.php';
        $migration->up();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'ToneflixCode\\KudiSmsNotification\\Tests\\Database\\Factories\\'.
            class_basename(
                $modelName
            ).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            FileableServiceProvider::class,
        ];
    }
}
