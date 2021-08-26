<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\User;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Router\Response;

$group
    ->get('/', Authorization::middleware(true, true), function () {
        $ret = Functions::pagination(
            Functions::sort(User::query())
        )->jsonSerialize();

        $ret["items"] = array_map(function ($user) {
            return $user->jsonSerialize(true);
        }, $ret["items"]->toArray());

        return $ret;
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
