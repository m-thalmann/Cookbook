<?php

namespace App\Providers;

use App\Http\Resources\PaginationResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        $this->definePasswordRules();
        $this->registerResponseMarcos();

        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());
    }

    private function definePasswordRules() {
        Password::defaults(function () {
            if (App::isProduction() || App::environment('testing')) {
                return Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols();
            } else {
                return Password::min(3);
            }
        });
    }

    private function registerResponseMarcos() {
        Response::macro('pagination', function (callable $callback) {
            $perPage = request(
                'per_page',
                config('app.pagination.default_per_page')
            );
            $value = $callback($perPage);

            return Response::make(new PaginationResource($value));
        });
    }
}
