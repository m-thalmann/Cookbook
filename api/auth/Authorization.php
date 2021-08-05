<?php

namespace API\Auth;

use API\Config\Config;
use API\Models\User;
use PAF\Router\Response;

class Authorization {
    /**
     * @var User The authenticated user
     */
    private static $user = null;

    /**
     * Encrypts the password using the provided salt
     * 
     * @param string $password
     * @param string $salt
     * 
     * @return string The encrypted password
     */
    public static function encryptPassword($password, $salt) {
        return hash_hmac(
            'sha256',
            $password . $salt,
            Config::get('password.secret')
        );
    }

    /**
     * Generates a new token for the given user
     * 
     * @param User $user
     * 
     * @return string|null The token or null if an error occured
     */
    public static function generateToken($user) {
        if ($user instanceof User) {
            return \JWT::encode(Config::get('token.secret'), [
                "user_id" => $user->id,
                "user_email" => $user->email,
                "user_name" => $user->name,
                "user_lastUpdated" => $user->lastUpdated,
                "exp" => time() + Config::get('token.ttl'),
            ]);
        }

        return null;
    }

    /**
     * Checks whether the token is correct.
     * - If it is correct it sets the user
     * 
     * @param string $token
     * 
     * @return bool Whether the token was correct or not
     */
    public static function authorize($token) {
        $data = \JWT::decode(Config::get("token.secret"), $token);

        $id = $data["user_id"];
        $lastUpdated = $data["user_lastUpdated"];

        $user = User::get("id = ?", [$id])->getFirst();

        if ($user && $user->lastUpdated === $lastUpdated) {
            self::$user = $user;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks the credentials.
     * - If they are correct it sets the user, generates a token and returns it
     * - If they are incorrect it returns null
     * 
     * @param string $email
     * @param string $password
     * 
     * @return string|null The token or null, if incorrect
     */
    public static function login($email, $password) {
        $user = User::get("email = :email", [
            "email" => $email,
        ])->getFirst();

        if (
            $user &&
            $user->password ===
                self::encryptPassword($password, $user->passwordSalt)
        ) {
            $token = self::generateToken($user);

            if ($token) {
                self::$user = $user;

                return $token;
            }
        }

        return null;
    }

    /**
     * Returns the user that is authorized or null if not authorized
     * 
     * @return User|null
     */
    public static function user() {
        return self::$user;
    }

    /**
     * Whether the client is authorized
     * 
     * @return bool
     */
    public static function isAuthorized() {
        return self::$user !== null;
    }

    /**
     * Returns a middleware-function for the PAF-Router, which checks whether
     * authorization is provided and it is valid.
     * 
     * @param bool $mustBeAuthorized Whether the authorization must succeed, to be able to request the route
     * 
     * @return callable The middleware function
     */
    public static function middleware($mustBeAuthorized = true) {
        return function ($request, $next = null) use ($mustBeAuthorized) {
            try {
                if ($request["authorization"] === null) {
                    throw new UnauthorizedException();
                }

                $token_parts = explode(" ", $request["authorization"]); // token form: '<type> <token>'

                if (count($token_parts) != 2) {
                    throw new UnauthorizedException();
                }

                list($token_type, $token) = $token_parts;

                // accept only bearer tokens
                if ($token_type !== "Bearer") {
                    throw new UnauthorizedException();
                }

                try {
                    if (!self::authorize($token)) {
                        throw new UnauthorizedException();
                    }
                } catch (\ExpiredException $e) {
                    throw new UnauthorizedException("Expired");
                } catch (\Exception $e) {
                    throw new UnauthorizedException();
                }
            } catch (UnauthorizedException $e) {
                if ($mustBeAuthorized || $next === null) {
                    return Response::unauthorized(["info" => $e->getMessage()]);
                }
            }

            if ($next !== null) {
                return $next($request);
            } else {
                return Response::ok([
                    "user" => self::$user,
                    "info" => "Authorized",
                ]);
            }
        };
    }
}
