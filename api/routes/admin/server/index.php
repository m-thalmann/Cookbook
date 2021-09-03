<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\config\Config;

$group->get('/config', Authorization::middleware(true, true), function () {
    return Config::getConfig(array_diff(Config::KEYS, Config::HIDDEN_KEYS));
});
