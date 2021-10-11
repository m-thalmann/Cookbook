<?php

namespace API\routes;

use API\auth\Authorization;
use API\config\Config;
use API\inc\ApiException;
use API\inc\Functions;
use API\inc\Mailer;
use API\inc\Validation;
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
        $data = $req["post"] ?? [];

        if ($data["email"] && $data["password"]) {
            $user = User::getByEmail($req["post"]["email"]);

            if ($user) {
                $canLogin = true;

                if (
                    $user->hasTooManyBadLogins() &&
                    Config::get("hcaptcha.enabled")
                ) {
                    if (array_key_exists("hcaptchaToken", $data)) {
                        if (
                            !Functions::validateHCaptcha($data["hcaptchaToken"])
                        ) {
                            throw ApiException::forbidden(
                                "hcaptcha_invalid",
                                "hCaptcha-Token invalid"
                            );
                        }
                    } else {
                        $canLogin = false;
                    }
                }

                if (
                    $canLogin &&
                    Authorization::checkUserPassword($user, $data["password"])
                ) {
                    $token = Authorization::login($user);

                    if ($token) {
                        $user->correctLogin();

                        return Response::ok([
                            "user" => Authorization::user()->getAuthUserJSON(),
                            "token" => "Bearer $token",
                        ]);
                    } else {
                        throw ApiException::forbidden(
                            "email_not_verified",
                            "Email not verified"
                        );
                    }
                }

                $user->badLogin();
            }

            return Response::notFound("Email or password wrong");
        } else {
            throw ApiException::badRequest(
                "default",
                "Email and password expected"
            );
        }
    })
    ->post('/register', function ($req) {
        if (!Config::get("registration_enabled", true)) {
            throw ApiException::methodNotAllowed(
                "registration_disabled",
                "Registration is disabled"
            );
        }

        $data = $req["post"] ?? [];

        if (Config::get("hcaptcha.enabled")) {
            if (array_key_exists("hcaptchaToken", $data)) {
                if (!Functions::validateHCaptcha($data["hcaptchaToken"])) {
                    throw ApiException::forbidden(
                        "hcaptcha_invalid",
                        "hCaptcha-Token invalid"
                    );
                }
            } else {
                throw ApiException::badRequest(
                    "default",
                    "hCaptcha-Token required"
                );
            }
        }

        if (array_key_exists("hcaptchaToken", $data)) {
            unset($data["hcaptchaToken"]);
        }

        Database::get()->beginTransaction();

        $user = User::fromValues($data);

        if(!Config::get("mail.enabled")){
            $user->clearEmailVerification();
        }

        try {
            $user->save();
        } catch (InvalidException $e) {
            throw ApiException::badRequest(
                "validation",
                Validation::getErrorMessages($user)
            );
        } catch (DuplicateException $e) {
            throw ApiException::conflict(
                "user_email",
                "A user with this email already exists"
            );
        }

        if (Config::get("email_verification.enabled") && Config::get("mail.enabled")) {
            try {
                Mailer::sendEmailVerification($user);
            } catch (\Exception $e) {
                Database::get()->rollBack();
                throw $e;
            }
        }

        Database::get()->commit();

        return Response::created($user->getAuthUserJSON());
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
            throw ApiException::forbidden(
                "old_password_wrong",
                "Old password is wrong"
            );
        }

        unset($data["oldPassword"]);

        $sendVerifyEmail = false;

        if(array_key_exists("email", $data) && $data["email"] !== $user->email && Config::get("mail.enabled")){
            $user->generateVerifyEmailCode();
            $sendVerifyEmail = Config::get("email_verification.enabled");
        }

        $user->editValues($data, true);

        Database::get()->beginTransaction();

        try {
            $user->save();
        } catch (InvalidException $e) {
            throw ApiException::badRequest(
                "validation",
                Validation::getErrorMessages($user)
            );
        } catch (DuplicateException $e) {
            throw ApiException::conflict(
                "user_email",
                "A user with this email already exists"
            );
        }

        if ($sendVerifyEmail) {
            try {
                Mailer::sendEmailVerification($user);
            } catch (\Exception $e) {
                Database::get()->rollBack();
                throw $e;
            }
        }

        Database::get()->commit();

        return $user;
    })
    ->delete('/', Authorization::middleware(), function () {
        if (Authorization::user()->isAdmin) {
            if (User::query("isAdmin = 1")->count() === 1) {
                throw ApiException::forbidden(
                    "last_admin",
                    "You are the last admin"
                );
            }
        }

        if (User::query("id = ?", [Authorization::user()->id])->delete()) {
            RecipeImage::deleteOrphanImages();
            return Response::ok();
        } else {
            throw ApiException::error("default", "Error deleting user");
        }
    })
    ->post('/verifyEmail', function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["email"]) || empty($data["code"])) {
            throw ApiException::badRequest(
                "default",
                "Email and code expected"
            );
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
                throw ApiException::forbidden(
                    "verification_code_wrong_or_expired",
                    "Verification code is wrong or has expired"
                );
            }
        }

        throw ApiException::error("default", "Error verifying email");
    })
    ->post('/verifyEmail/resend', function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["email"])) {
            throw ApiException::badRequest("default", "Email expected");
        }

        if(!Config::get("mail.enabled")){
            throw ApiException::methodNotAllowed("email_disabled", "Emails are disabled on the server");
        }

        $user = User::get("email = ?", [$data["email"]])->getFirst();

        if (!($user instanceof User) || User::isEmailVerified($user)) {
            return Response::ok();
        }

        if ($user->verifyEmailCodeExpires < time()) {
            $user->generateVerifyEmailCode();
            $user->save();
        }

        Mailer::sendEmailVerification($user);

        return Response::ok();
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
            throw ApiException::badRequest(
                "default",
                "Email, token and password expected"
            );
        }

        $resetPassword = ResetPassword::search($data["email"], $data["token"]);

        if (!$resetPassword || !($user = $resetPassword->user())) {
            return Response::notFound();
        }

        $resetPassword->delete();

        $user->password = $data["password"];

        if (!$user->save()) {
            throw ApiException::error("default", "Error saving user");
        }

        return Response::ok();
    })
    ->post('/resetPassword/send', function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["email"])) {
            throw ApiException::badRequest("default", "Email expected");
        }

        if(!Config::get("mail.enabled")){
            throw ApiException::methodNotAllowed("email_disabled", "Emails are disabled on the server");
        }

        $user = User::get("email = ?", [$data["email"]])->getFirst();

        if ($user === null) {
            return Response::ok();
        }

        $resetPassword = ResetPassword::generate($user);

        Mailer::sendResetPassword($user, $resetPassword->token);

        return Response::ok();
    });
