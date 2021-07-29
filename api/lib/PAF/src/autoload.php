<?php

namespace PAF;

spl_autoload_register(function ($class) {
    if (strpos($class, __NAMESPACE__) !== 0) {
        return;
    }

    $class = str_replace('\\', '/', $class);

    $path = __DIR__ . '/' . substr($class, strlen(__NAMESPACE__) + 1) . '.php';

    require_once $path;
});
