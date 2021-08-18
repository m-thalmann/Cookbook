<?php

namespace API\routes;

use API\auth\Authorization;
use API\config\Config;
use API\inc\Functions;
use API\models\User;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Router\Response;

$group
    ->get('/', Authorization::middleware())
    ->post('/login', function ($req) {
        if ($req["post"]["email"] && $req["post"]["password"]) {
            $token = Authorization::login(
                $req["post"]["email"],
                $req["post"]["password"]
            );

            if ($token) {
                return Response::ok([
                    "info" => "Authorized",
                    "user" => Authorization::user(),
                    "token" => "Bearer $token",
                ]);
            } else {
                return Response::notFound([
                    "info" => "Email or password wrong",
                ]);
            }
        } else {
            return Response::badRequest([
                "info" => "Email and password expected",
            ]);
        }
    })
    ->post('/register', function ($req) {
        if (!Config::get("registration_enabled", true)) {
            return Response::methodNotAllowed([
                "info" => "Registration is disabled",
            ]);
        }

        $data = $req["post"] ? $req["post"] : [];

        if (Config::get("hcaptcha.enabled")) {
            if (array_key_exists("hcaptchaToken", $data)) {
                if (!Functions::validateHCaptcha($data["hcaptchaToken"])) {
                    return Response::forbidden([
                        "info" => "hCaptcha-Token invalid",
                    ]);
                }

                unset($data["hcaptchaToken"]);
            } else {
                return Response::badRequest([
                    "hcaptchaToken" => "hCaptcha-Token required",
                ]);
            }
        }

        $user = User::fromValues($data);

        try {
            $user->save();
        } catch (InvalidException $e) {
            return Response::badRequest(User::getErrors($user));
        } catch (DuplicateException $e) {
            return Response::conflict([
                "info" => "A user with this email already exists",
            ]);
        }

        $token = Authorization::generateToken($user);

        return Response::created([
            "info" => "Authorized",
            "user" => $user,
            "token" => "Bearer $token",
        ]);
    })
    ->put('/', Authorization::middleware(), function ($req) {
        $user = Authorization::user();

        $data = $req["post"] ?? [];

        if (
            !array_key_exists("oldPassword", $data) ||
            Authorization::encryptPassword(
                $data["oldPassword"],
                $user->passwordSalt
            ) !== $user->password
        ) {
            return Response::forbidden(["info" => "Old password is wrong"]);
        }

        unset($data["oldPassword"]);

        $user->editValues($data, true);

        try {
            $user->save();
        } catch (InvalidException $e) {
            return Response::badRequest(User::getErrors($user));
        } catch (DuplicateException $e) {
            return Response::conflict([
                "info" => "A user with this email already exists",
            ]);
        }

        return $user;
    })
    ->delete('/', Authorization::middleware(), function () {
        if (User::query("id = ?", [Authorization::user()->id])->delete()) {
            return Response::ok();
        } else {
            return Response::error();
        }
    })
    ->get('/registrationEnabled', function () {
        return Config::get("registration_enabled", true);
    });
