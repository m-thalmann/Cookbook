<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\config\Config;
use API\config\ConfigSettings;
use API\inc\ApiException;
use API\inc\Mailer;
use PAF\Router\Response;

$group
    ->get("/config", Authorization::middleware(true, true), function () {
        return Config::getConfig(ConfigSettings::getVisiblePaths());
    })
    ->put("/config", Authorization::middleware(true, true), function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["path"]) || !array_key_exists("value", $data)) {
            throw ApiException::badRequest(
                "default",
                "Path and/or value missing"
            );
        }

        if (!Config::edit($data["path"], $data["value"])) {
            throw ApiException::error("config.saving", "Error saving config");
        }

        return Response::ok();
    })
    ->post("/sendTestEmail", Authorization::middleware(true, true), function (
        $req
    ) {
        $data = $req["post"] ?? [];

        if (!array_key_exists("email", $data)) {
            throw ApiException::badRequest("default", "Email expected");
        }

        Mailer::send(
            $data["email"],
            "Cookbook test email",
            "This is a test email",
            true
        );

        return true;
    });
