<?php

namespace CodeTechNL\TaskBridgeFilament;

use Illuminate\Support\ServiceProvider;

class TaskBridgeFilamentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'taskbridge-filament');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/taskbridge-filament'),
            ], 'taskbridge-filament-views');
        }
    }
}
