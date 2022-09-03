<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\OAuthIdentity;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider as TwoAbstractProvider;
use TokenAuth\TokenAuth;

class OAuthController extends Controller {
    public function redirect(string $provider) {
        $driver = $this->getStatelessDriver($provider);

        return $driver->redirect();
    }

    public function login(string $provider) {
        $driver = $this->getStatelessDriver($provider);

        try {
            $oauthUser = $driver->user();
        } catch (ClientException $e) {
            throw ValidationException::withMessages([
                'credentials' => __('auth.oauth.failed'),
            ]);
        }

        $oauthIdentity = OAuthIdentity::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $oauthUser->getId())
            ->first();

        if ($oauthIdentity === null) {
            /**
             * @var User
             */
            $user = User::query()->firstOrCreate(
                [
                    'email' => $oauthUser->getEmail(),
                ],
                [
                    'first_name' => Str::beforeLast($oauthUser->getName(), ' '),
                    'last_name' => Str::afterLast($oauthUser->getName(), ' '),
                ]
            );

            $user->oauthIdentities()->create([
                'provider' => $provider,
                'provider_user_id' => $oauthUser->getId(),
            ]);
        } else {
            $user = $oauthIdentity->user;
        }

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            config('auth.token_names.refresh'),
            config('auth.token_names.access')
        );

        $user->load('oauthIdentities');
        // TODO: maybe also add to user controller?

        return JsonResource::make([
            'user' => UserResource::make($user),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ]);
    }

    private function getStatelessDriver(string $provider): Provider {
        $this->validateProvider($provider);

        $driver = Socialite::driver($provider);

        if ($driver instanceof TwoAbstractProvider) {
            $driver = $driver->stateless();
        }

        return $driver;
    }

    private function validateProvider(string $provider): void {
        $isValid = false;

        switch ($provider) {
            case 'github':
                $isValid = config('services.github.enabled', false);
                break;
        }

        if (!$isValid) {
            throw new AuthorizationException(
                __('auth.oauth.provider_not_supported')
            );
        }
    }
}

