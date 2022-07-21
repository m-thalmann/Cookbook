<?php

namespace App\Providers;

use HTMLPurifier;
use HTMLPurifier_Config;
use App\Services\HTMLPurifierService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class HTMLPurifierServiceProvider extends ServiceProvider implements
    DeferrableProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(HTMLPurifierService::class, function ($app) {
            return new HTMLPurifierService(
                new HTMLPurifier($this->getDefaultConfig())
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [HTMLPurifierService::class];
    }

    private function getDefaultConfig() {
        $config = HTMLPurifier_Config::createDefault();

        return $config;
    }
}
