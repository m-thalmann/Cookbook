<?php

namespace App\Providers;

use App\Services\HCaptchaService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class HCaptchaServiceProvider extends ServiceProvider implements
    DeferrableProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(HCaptchaService::class, function ($app) {
            return new HCaptchaService(config('services.hcaptcha.secret'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [HCaptchaService::class];
    }
}
