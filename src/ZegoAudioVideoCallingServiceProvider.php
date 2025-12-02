<?php

namespace ZegoAudioVideoCalling;

use Illuminate\Support\ServiceProvider;
use ZegoAudioVideoCalling\Services\PushNotificationService;
use ZegoAudioVideoCalling\Services\ZegoCloudService;

class ZegoAudioVideoCallingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/Config/zego-calling.php', 'zego-calling'
        );

        // Register services
        $this->app->singleton(PushNotificationService::class, function ($app) {
            return new PushNotificationService();
        });

        $this->app->singleton(ZegoCloudService::class, function ($app) {
            return new ZegoCloudService();
        });

        // Register facade
        $this->app->bind('zego-calling', function ($app) {
            return new ZegoCloudService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/Config/zego-calling.php' => config_path('zego-calling.php'),
        ], 'zego-calling-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations'),
        ], 'zego-calling-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/zego-calling'),
        ], 'zego-calling-views');

        // Publish assets
        $this->publishes([
            __DIR__.'/resources/js' => public_path('vendor/zego-calling/js'),
            __DIR__.'/resources/css' => public_path('vendor/zego-calling/css'),
        ], 'zego-calling-assets');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Load package routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        // Load package views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'zego-calling');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add any package commands here
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            PushNotificationService::class,
            ZegoCloudService::class,
            'zego-calling',
        ];
    }
}
