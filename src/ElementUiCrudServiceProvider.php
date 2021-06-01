<?php

namespace XT\ElementUiCrud;

use Illuminate\Support\ServiceProvider;
use XT\ElementUiCrud\Console\CrudGenerator;

class ElementUiCrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'element-ui-crud');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'element-ui-crud');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            // Publishing the config.
//            $this->publishes([
//                __DIR__.'/../config/config.php' => config_path('element-ui-crud.php'),
//            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/element-ui-crud'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/element-ui-crud'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/element-ui-crud'),
            ], 'lang');*/

            // Registering package commands.
             $this->commands([
                 CrudGenerator::class,
             ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
//        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'element-ui-crud');

        // Register the main class to use with the facade
        $this->app->singleton('element-ui-crud', function () {
            return new ElementUiCrud;
        });
    }
}
