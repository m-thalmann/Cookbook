<?php

namespace API\Auth;

use API\Config\Config;
use API\Models\User;
use PAF\Router\Response;

class Authorization {
    private static $user = null;

    public static function encryptPassword($password, $salt) {
        return hash_hmac(
            'sha256',
            $password . $salt,
            Config::get('password.secret')
        );
    }

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

    public static function authorize($token) {
        $data = \JWT::decode(Config::get("token.secret"), $token);

        $id = $data["id"];
        $lastUpdated = $data["lastUpdated"];

        $user = User::get("id = ?", [$id])->getFirst();

        if ($user && $user->lastUpdated === $lastUpdated) {
            self::$user = $user;

            return true;
        } else {
            return false;
        }
    }

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

    public static function user() {
        return self::$user;
    }

    public static function middleware() {
        return function ($request, $next = null) {
            $response = Response::unauthorized(["info" => "Unauthorized"]);

            if ($request["authorization"] === null) {
                return $response;
            }

            $token_parts = explode(" ", $request["authorization"]); // token form: '<type> <token>'

            if (count($token_parts) != 2) {
                return $response;
            }

            list($token_type, $token) = $token_parts;

            // accept only bearer tokens
            if ($token_type !== "Bearer") {
                return $response;
            }

            try {
                if (!self::authorize($token)) {
                    return $response;
                }
            } catch (\ExpiredException $e) {
                return Response::unauthorized("Expired");
            } catch (\Exception $e) {
                return $response;
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
