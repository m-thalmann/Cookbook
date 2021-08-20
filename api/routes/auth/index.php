<?php

namespace API\routes;

use API\auth\Authorization;
use API\config\Config;
use API\inc\Functions;
use API\inc\Mailer;
use API\models\User;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Model\Model;
use PAF\Router\Response;

$group
    ->get('/', Authorization::middleware())
    ->post('/login', function ($req) {
        if ($req["post"]["email"] && $req["post"]["password"]) {
            $user = Authorization::getUser(
                $req["post"]["email"],
                $req["post"]["password"]
            );

            if ($user) {
                $token = Authorization::login($user);

                if ($token) {
                    return Response::ok([
                        "info" => "Authorized",
                        "user" => Authorization::user(),
                        "token" => "Bearer $token",
                    ]);
                } else {
                    return Response::forbidden([
                        "info" => "Email not verified",
                    ]);
                }
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

        Model::db()->beginTransaction();

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

        if (Config::get("email_verification")) {
            if (!Mailer::sendEmailVerification($user)) {
                Model::db()->rollBack();

                return Response::error([
                    "info" => "Error sending verification-email",
                ]);
            }
        }

        Model::db()->commit();

        return Response::created([
            "info" => "Authorized",
            "user" => $user,
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
    ->post('/verifyEmail', function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["email"]) || empty($data["code"])) {
            return Response::badRequest(["info" => "Email and code expected"]);
        }

        $user = User::get("email = ?", [$data["email"]])->getFirst();

        if ($user instanceof User) {
            if ($user === null || User::isEmailVerified($user)) {
                return Response::notFound();
            }

            if ($user->verifyEmail($data["code"])) {
                if ($user->save()) {
                    return Response::ok();
                }
            } else {
                return Response::forbidden([
                    "info" => "Verification code is wrong",
                ]);
            }
        }

        return Response::error();
    })
    ->post('/verifyEmail/resend', function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["email"])) {
            return Response::badRequest(["info" => "Email expected"]);
        }

        $user = User::get("email = ?", [$data["email"]])->getFirst();

        if ($user === null || User::isEmailVerified($user)) {
            return Response::notFound();
        }

        if (Mailer::sendEmailVerification($user)) {
            return Response::ok();
        }

        return Response::error();
    })
    ->get('/registrationEnabled', function () {
        return Config::get("registration_enabled", true);
    });
