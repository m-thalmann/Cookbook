<?php

namespace API\routes;

use API\auth\Authorization;
use API\config\Config;
use API\inc\Functions;
use API\inc\Mailer;
use API\models\RecipeImage;
use API\models\ResetPassword;
use API\models\User;
use PAF\Model\Database;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
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

        $data = $req["post"] ?? [];

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

        Database::get()->beginTransaction();

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

        if (Config::get("email_verification.enabled")) {
            if (!Mailer::sendEmailVerification($user)) {
                Database::get()->rollBack();

                return Response::error([
                    "info" => "Error sending verification-email",
                ]);
            }
        }

        Database::get()->commit();

        return Response::created([
            "info" => "Authorized",
            "user" => $user,
        ]);
    })
    ->put('/', Authorization::middleware(), function ($req) {
        $user = Authorization::user();

        $data = $req["post"] ?? [];

        $oldPasswordRequired = false;

        foreach (User::EDIT_PASSWORD_REQUIRED_PROPERTIES as $property) {
            if (array_key_exists($property, $data)) {
                $oldPasswordRequired = true;
                break;
            }
        }

        if (
            $oldPasswordRequired &&
            (!array_key_exists("oldPassword", $data) ||
                Authorization::encryptPassword(
                    $data["oldPassword"],
                    $user->passwordSalt
                ) !== $user->password)
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
        if (Authorization::user()->isAdmin) {
            if (User::query("isAdmin = 1")->count() === 1) {
                return Response::forbidden([
                    "info" => "You are the last admin",
                ]);
            }
        }

        if (User::query("id = ?", [Authorization::user()->id])->delete()) {
            RecipeImage::deleteOrphanImages();
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

        if ($user === null || $user instanceof User) {
            if ($user === null || User::isEmailVerified($user)) {
                return Response::ok();
            }

            if ($user->verifyEmail($data["code"])) {
                if ($user->save()) {
                    return Response::ok();
                }
            } else {
                return Response::forbidden([
                    "info" => "Verification code is wrong or has expired",
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

        if (!($user instanceof User) || User::isEmailVerified($user)) {
            return Response::ok();
        }

        if ($user->verifyEmailCodeExpires < time()) {
            $user->generateVerifyEmailCode();
            $user->save();
        }

        if (Mailer::sendEmailVerification($user)) {
            return Response::ok();
        }

        return Response::error();
    })
    ->get('/registrationEnabled', function () {
        return Config::get("registration_enabled", true);
    })
    ->post('/resetPassword', function ($req) {
        $data = $req["post"] ?? [];

        if (
            empty($data["email"]) ||
            empty($data["token"]) ||
            empty($data["password"])
        ) {
            return Response::badRequest([
                "info" => "Email, token and password expected",
            ]);
        }

        $resetPassword = ResetPassword::search($data["email"], $data["token"]);

        if (!$resetPassword || !($user = $resetPassword->user())) {
            return Response::notFound();
        }

        $resetPassword->delete();

        $user->password = $data["password"];

        if (!$user->save()) {
            return Response::error();
        }

        return Response::ok();
    })
    ->post('/resetPassword/send', function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["email"])) {
            return Response::badRequest(["info" => "Email expected"]);
        }

        $user = User::get("email = ?", [$data["email"]])->getFirst();

        if ($user === null) {
            return Response::ok();
        }

        $resetPassword = ResetPassword::generate($user);

        if (Mailer::sendResetPassword($user, $resetPassword->token)) {
            return Response::ok();
        }

        return Response::error();
    });
