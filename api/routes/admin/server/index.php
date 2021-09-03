<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\config\Config;
use API\config\ConfigSettings;
use PAF\Router\Response;

$group
    ->get('/config', Authorization::middleware(true, true), function () {
        return Config::getConfig(ConfigSettings::getVisiblePaths());
    })
    ->put('/config', Authorization::middleware(true, true), function ($req) {
        $data = $req["post"] ?? [];

        if (empty($data["path"]) || !array_key_exists("value", $data)) {
            return Response::badRequest([
                "info" => "Path and/or value missing",
            ]);
        }

        try {
            if (!Config::edit($data["path"], $data["value"])) {
                return Response::error();
            }
        } catch (\InvalidArgumentException $e) {
            return Response::forbidden(["info" => $e->getMessage()]);
        }

        return Response::ok();
    });
