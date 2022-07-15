<?php

namespace App\Api\V1\Controllers\Auth;

use App\Api\V1\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TokenAuth\TokenAuth;

class AuthenticationController extends Controller {
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException(__('auth.failed'));
        }

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            config('auth.token_names.refresh'),
            config('auth.token_names.access')
        );

        return JsonResource::make([
            'user' => UserResource::make($user),
            'refresh_token' => $refreshToken->plainTextToken,
            'access_token' => $accessToken->plainTextToken,
        ]);
    }

    public function register(Request $request) {
        if (!config('app.registration_enabled')) {
            throw new HttpException(403, __('auth.registration_disabled'));
        }

        $data = $request->validate([
            'first_name' => ['required', 'filled', 'string', 'max:255'],
            'last_name' => ['required', 'filled', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::default()],
            'language_code' => ['nullable', 'min:2', 'max:2'],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        event(new Registered($user));

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            config('auth.token_names.refresh'),
            config('auth.token_names.access')
        );

        return JsonResource::make([
            'user' => UserResource::make($user),
            'refresh_token' => $refreshToken->plainTextToken,
            'access_token' => $accessToken->plainTextToken,
        ])
            ->response()
            ->setStatusCode(201);
    }

    public function refresh() {
        [$refreshToken, $accessToken] = TokenAuth::rotateRefreshToken(
            config('auth.token_names.access')
        );

        return JsonResource::make([
            'refresh_token' => $refreshToken->plainTextToken,
            'access_token' => $accessToken->plainTextToken,
        ])
            ->response()
            ->setStatusCode(201);
    }

    public function logout() {
        authUser()
            ->currentToken()
            ->deleteAllTokensFromSameGroup();

        return response()->noContent();
    }

    public function viewAuthUser() {
        return UserResource::make(authUser());
    }
}
