<?php

namespace API\auth;

use API\config\Config;
use API\models\User;
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
     * @return string|null The token or null if an error occurred
     */
    public static function generateToken($user) {
        if ($user instanceof User) {
            return \JWT::encode(Config::get('token.secret'), [
                "user_id" => $user->id,
                "user_email" => $user->email,
                "user_name" => $user->name,
                "user_isAdmin" => $user->isAdmin,
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

        if (
            $user &&
            $user->lastUpdated === $lastUpdated &&
            (!Config::get("email_verification.enabled", false) ||
                User::isEmailVerified($user))
        ) {
            self::$user = $user;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieves the user with the given credentials
     *
     * @param string $email
     * @param string $password
     *
     * @return User|null The user or null, if incorrect credentials
     */
    public static function getUser($email, $password) {
        $user = User::get("email = :email", [
            "email" => $email,
        ])->getFirst();

        if (
            $user &&
            $user->password ===
                self::encryptPassword($password, $user->passwordSalt)
        ) {
            return $user;
        }

        return null;
    }

    /**
     * Checks whether the user is allowed to log in
     * - If he is allowed the user is set, a token is generated and returned
     * - If not, null is returned
     *
     * @param User $user
     *
     * @return string|null The token or null, if not allowed
     */
    public static function login($user) {
        if (
            !Config::get("email_verification.enabled", false) ||
            User::isEmailVerified($user)
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
     * authorization is provided, and it is valid.
     *
     * @param bool $mustBeAuthorized Whether the authorization must succeed, to be able to request the route
     *
     * @return callable The middleware function
     */
    public static function middleware(
        $mustBeAuthorized = true,
        $mustBeAdmin = false
    ) {
        return function ($request, $next = null) use (
            $mustBeAuthorized,
            $mustBeAdmin
        ) {
            try {
                if (
                    $request["authorization"] === null &&
                    empty($_GET['token'])
                ) {
                    throw new UnauthorizedException();
                }

                $token = $request["authorization"];

                if ($token === null) {
                    $token = $_GET['token'];
                }

                $tokenParts = explode(" ", $token); // token form: '<type> <token>'

                if (count($tokenParts) != 2) {
                    throw new UnauthorizedException();
                }

                list($tokenType, $token) = $tokenParts;

                // accept only bearer tokens
                if ($tokenType !== "Bearer") {
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

                if ($mustBeAdmin && !self::$user->isAdmin) {
                    throw new UnauthorizedException("Must be admin");
                }
            } catch (UnauthorizedException $e) {
                if ($mustBeAuthorized || $next === null) {
                    return Response::unauthorized(["info" => $e->getMessage()]);
                }
                if (!empty($token)) {
                    header('X-Logout: true');
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
