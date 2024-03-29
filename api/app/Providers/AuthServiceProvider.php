<?php

namespace App\Providers;

use App\Auth\TokenGuard;
use App\Models\AuthToken;
use App\Models\Cookbook;
use App\Models\Ingredient;
use App\Models\User;
use App\Policies\AuthTokenPolicy;
use App\Policies\CookbookPolicy;
use App\Policies\IngredientPolicy;
use App\Policies\RecipePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use TokenAuth\Facades\TokenAuth;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuthToken::class => AuthTokenPolicy::class,
        Cookbook::class => CookbookPolicy::class,
        Ingredient::class => IngredientPolicy::class,
        Recipe::class => RecipePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register() {
        parent::register();

        TokenAuth::useAuthToken(AuthToken::class);
        TokenAuth::useTokenGuard(TokenGuard::class);
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();
    }
}
