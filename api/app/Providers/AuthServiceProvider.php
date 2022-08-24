<?php

namespace App\Providers;

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
use TokenAuth\TokenAuth;

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

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();

        TokenAuth::useAuthTokenModel(AuthToken::class);
    }
}
