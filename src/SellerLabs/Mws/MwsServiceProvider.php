<?php
// From https://github.com/cviebrock/laravel5-package-template/blob/master/src/ServiceProvider.php

namespace SellerLabs\Mws;

use Illuminate\Support\ServiceProvider as ServiceProvider;

class MwsServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $configPath = __DIR__ . '/../config/mws.php';
        $this->publishes([$configPath => config_path('mws.php')]);
        $this->mergeConfigFrom($configPath, 'mws');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(MwsInterface::class, Mws::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [
            MwsInterface::class,
        ];
    }
}