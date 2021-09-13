<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\config\Config;
use API\inc\Functions;
use API\inc\Mailer;
use API\models\RecipeImage;
use API\models\User;
use PAF\Model\Database;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Router\Response;

$group
    ->get('/', Authorization::middleware(true, true), function () {
        $query = "1";
        $queryValues = [];

        if (!empty($_GET['search'])) {
            $query = "email LIKE :search OR name LIKE :search";
            $queryValues["search"] = "%" . urldecode($_GET['search']) . "%";
        }

        $ret = Functions::pagination(
            Functions::sort(User::query($query, $queryValues))
        )->jsonSerialize();

        $ret["items"] = array_map(function ($user) {
            return $user->jsonSerialize(true);
        }, $ret["items"]->toArray());

        return $ret;
    })
    ->get('/id/{{i:id}}', Authorization::middleware(true, true), function (
        $req
    ) {
        $user = User::getById($req["params"]["id"]);

        if ($user === null) {
            return Response::notFound();
        }

        return $user->jsonSerialize(true);
    })
    ->post('/', Authorization::middleware(true, true), function ($req) {
        $data = $req["post"] ?? [];

        $verifyEmail = true;
        $isAdmin = false;

        if (array_key_exists("isAdmin", $data)) {
            $isAdmin = $data["isAdmin"];
            unset($data["isAdmin"]);
        }
        if (array_key_exists("verifyEmail", $data)) {
            $verifyEmail = $data["verifyEmail"];
            unset($data["verifyEmail"]);
        }

        Database::get()->beginTransaction();

        $user = User::fromValues($data);

        if ($isAdmin) {
            $user->setIsAdmin(true);
        }
        if (!$verifyEmail) {
            $user->clearEmailVerification();
        }

        try {
            $user->save();
        } catch (InvalidException $e) {
            return Response::badRequest(User::getErrors($user));
        } catch (DuplicateException $e) {
            return Response::conflict([
                "info" => "A user with this email already exists",
            ]);
        }

        if ($verifyEmail && Config::get("email_verification.enabled")) {
            if (!Mailer::sendEmailVerification($user)) {
                Database::get()->rollBack();

                return Response::error([
                    "info" => "Error sending verification-email",
                ]);
            }
        }

        Database::get()->commit();

        return Response::created($user->jsonSerialize(true));
    })
    ->put('/id/{{i:id}}', Authorization::middleware(true, true), function (
        $req
    ) {
        if ($req["params"]["id"] === Authorization::user()->id) {
            return Response::forbidden(["info" => "You can't update yourself"]);
        }

        $user = User::getById($req["params"]["id"]);

        if ($user === null) {
            return Response::notFound();
        }

        $data = $req["post"] ?? [];

        if (array_key_exists("isAdmin", $data)) {
            $user->setIsAdmin($data["isAdmin"]);
            unset($data["isAdmin"]);
        }
        if (array_key_exists("emailVerified", $data)) {
            if ($data["emailVerified"]) {
                if (!User::isEmailVerified($user)) {
                    $user->clearEmailVerification();
                }
            } else {
                if (User::isEmailVerified($user)) {
                    $user->generateVerifyEmailCode();
                }
            }
            unset($data["emailVerified"]);
        }

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

        return $user->jsonSerialize(true);
    })
    ->delete('/id/{{i:id}}', Authorization::middleware(true, true), function (
        $req
    ) {
        if ($req["params"]["id"] === Authorization::user()->id) {
            return Response::forbidden(["info" => "You can't delete yourself"]);
        }

        if (User::query("id = ?", [$req["params"]["id"]])->delete()) {
            RecipeImage::deleteOrphanImages();
            
            return Response::ok();
        } else {
            return Response::error();
        }
    })
    ->post(
        '/id/{{i:id}}/resetPassword',
        Authorization::middleware(true, true),
        function ($req) {
            $user = User::getById($req["params"]["id"]);

            if ($user === null) {
                return Response::notFound();
            }

            $password = substr(Functions::getRandomString(), 0, 10);

            $user->password = $password;

            if (!$user->save()) {
                return Response::error();
            }

            return [
                "user" => $user->jsonSerialize(true),
                "password" => $password,
            ];
        }
    );
