<?php

namespace Httpsnader1\DatabaseControllers;

use Illuminate\Support\ServiceProvider;

class DatabaseControllersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'database-controllers');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'database-controllers');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/database-controllers.php' => config_path('database-controllers.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/database-controllers'),
            ], 'views');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/database-controllers.php', 'database-controllers');
    }
}
