<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\config\Config;

const FORBIDDEN_KEYS = ["database.password", "token.secret", "password.secret", "hcaptcha.secret", "mail.smtp.password"];

$group->get('/config', Authorization::middleware(true, true), function () {
    return Config::getConfig(FORBIDDEN_KEYS);
});
