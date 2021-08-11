<?php

namespace API\config;

class Config {
    private static $config = null;

    public static function load($file) {
        if (self::$config !== null) {
            return;
        }

        self::$config = @json_decode(file_get_contents($file), true);

        if (self::$config === null || self::$config === false) {
            throw new \Exception('Config could not be loaded');
        }
    }

    /**
     * Gets a value from the config
     *
     * @param string $path The path to the value; if nested use '.'
     *
     * @return mixed|null The value or null if not found
     */
    public static function get($path, $default = null) {
        $curr = &self::$config;

        foreach (explode('.', $path) as $key) {
            if (!isset($curr[$key])) {
                return $default;
            }

            $curr = &$curr[$key];
        }

        return $curr;
    }
}

Config::load(__DIR__ . "/config.json");
