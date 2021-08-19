<?php

namespace API;

$autoload = @json_decode(@file_get_contents(__DIR__ . "/autoload.json"));

spl_autoload_register(function ($class) use ($autoload) {
    $path = null;

    if (strpos($class, __NAMESPACE__) === 0) {
        $class = str_replace('\\', '/', $class);

        $path =
            __DIR__ . '/' . substr($class, strlen(__NAMESPACE__) + 1) . '.php';
    }

    if ($path === null && $autoload) {
        foreach ($autoload as $namespace => $dir) {
            if (strpos($class, $namespace) === 0) {
                $class = substr($class, strlen($namespace) + 1);
                $class = str_replace('\\', '/', $class);

                $path = __DIR__ . "/$dir/" . $class . ".php";
                break;
            }
        }
    }

    if ($path === null) {
        return;
    }

    require_once $path;
});
