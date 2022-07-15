<?php

namespace App\Providers;

use App\Models\AuthToken;
use App\Models\User;
use App\Policies\AuthTokenPolicy;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
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
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();
        $this->setupTokenAuth();

        // set the function to generate the url for a password-reset
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return config('app.frontend_url') .
                "/password-reset/{$notifiable->getEmailForPasswordReset()}/$token";
        });

        // set the function to generate the url for a email-verification
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            list(
                'signature' => $signature,
                'expires' => $expires,
            ) = signedRouteParameters(
                'auth.email.verify',
                [
                    'id' => $id,
                    'hash' => $hash,
                ],
                Carbon::now()->addMinutes(
                    config('auth.verification.expire', 60)
                )
            );

            return config('app.frontend_url') .
                "/verify-email/$id/$hash?expires=$expires&signature=$signature";
        });
    }

    private function setupTokenAuth() {
        TokenAuth::dontSaveTokenOnAuthentication();
        TokenAuth::useAuthTokenModel(AuthToken::class);
    }
}
