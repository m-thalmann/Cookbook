<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

if (!function_exists('authUser')) {
    /**
     * Returns the authenticated user
     *
     * @return User|null
     */
    function authUser(): ?User {
        return auth()->user();
    }
}

if (!function_exists('signedRouteParameters')) {
    /**
     * Returns the "signature" (and "expires" if temporary) params of the signed route.
     * Used to pass these to the frontend, which has to rebuild the url
     *
     * @param string $routeName The route-name
     * @param array $parameters The parameters to pass to the route
     * @param \DateTimeInterface|\DateInterval|int|null $expires The timestamp when the signature expires or null if never expires
     *
     * @return array Map containing the "signature" and "expires" keys
     */
    function signedRouteParameters(
        $routeName,
        $parameters = [],
        $expires = null
    ): array {
        $verifyUrl = URL::signedRoute($routeName, $parameters, $expires);

        $queryString = Str::after($verifyUrl, '?');
        parse_str($queryString, $query);

        return [
            'signature' => $query['signature'],
            'expires' => $expires !== null ? $query['expires'] : null,
        ];
    }
}
