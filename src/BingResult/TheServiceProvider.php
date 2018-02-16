<?php

namespace Semok\Scrapper\BingResult;

use Illuminate\Support\ServiceProvider;

class TheServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {

            // Publishing the configuration file.
            $this->publishes([
                __DIR__.'/config/scrapper.php' => config_path('semok/scrapper/bingresult.php'),
            ], 'semok.config');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/scrapper.php', 'semok.scrapper.bingresult');

        // Register the service the package provides.
        $this->app->singleton('semok.scrapper.bingresult', function ($app) {
            return new BingResult;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['semok.scrapper.bingresult'];
    }
}
