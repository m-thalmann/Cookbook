<?php

namespace API\config;

use API\inc\ApiException;

class Config {
    private static $baseConfig = null;
    private static $config = null;

    /**
     * Loads the base-config from the json-file
     *
     * @param string $file The path to the json-config-file
     *
     * @throws ApiException
     */
    public static function loadBaseConfig($file) {
        if (self::$baseConfig !== null) {
            return;
        }

        self::$baseConfig = @json_decode(file_get_contents($file), true);

        if (self::$baseConfig === null || self::$baseConfig === false) {
            throw ApiException::error(
                "config.loading",
                "Base-Config could not be loaded"
            );
        }
    }

    /**
     * Loads the config from the database
     *
     * @see ConfigSettings::loadConfig()
     */
    public static function loadConfig() {
        self::$config = ConfigSettings::loadConfig();
    }

    /**
     * Gets a value from the config (first checking the values from the database)
     *
     * @param string $path The path to the value; if nested use '.'
     * @param mixed $default The default value to return, if the path is not found
     *
     * @return mixed|null The value or null if not found
     */
    public static function get($path, $default = null) {
        if (array_key_exists($path, self::$config)) {
            if (!self::$config[$path]["parsed"]) {
                self::$config[$path][
                    "value"
                ] = ConfigSettings::parseConfigValue(
                    self::$config[$path]["datatype"],
                    self::$config[$path]["value"],
                    self::$config[$path]["encrypted"]
                );
                self::$config[$path]["parsed"] = true;
            }

            return self::$config[$path]["value"];
        }

        if (
            array_key_exists($path, ConfigSettings::SETTINGS) &&
            (!array_key_exists("baseConfig", ConfigSettings::SETTINGS[$path]) ||
                !ConfigSettings::SETTINGS[$path]["baseConfig"])
        ) {
            return ConfigSettings::SETTINGS[$path]["defaultValue"];
        }

        return self::getBaseConfig($path, $default);
    }

    /**
     * Gets a value from the base-config
     *
     * @param string $path The path to the value; if nested use '.'
     * @param mixed $default The default value to return, if the path is not found
     *
     * @return mixed|null The value or null if not found
     */
    public static function getBaseConfig($path, $default = null) {
        $curr = &self::$baseConfig;

        foreach (explode('.', $path) as $key) {
            if (!isset($curr[$key])) {
                return $default;
            }

            $curr = &$curr[$key];
        }

        return $curr;
    }

    /**
     * Gets multiple values from the config (first checking the values from the database)
     *
     * @param string[] $paths The paths to the values; if nested use '.'
     *
     * @return array The values
     */
    public static function getConfig($paths) {
        $config = [];

        foreach ($paths as $path) {
            $config[$path] = self::get($path);
        }

        return $config;
    }

    /**
     * Edits a config-value and reloads the values on success
     *
     * @param string $path The config-path
     * @param mixed $value The value to set
     *
     * @see ConfigSettings::saveConfigValue()
     *
     * @throws ApiException If the path is not editable/does not exist or the value is not valid
     *
     * @return boolean Whether the value was saved or not
     */
    public static function edit($path, $value) {
        if (ConfigSettings::saveConfigValue($path, $value)) {
            self::loadConfig();
            return true;
        }

        return false;
    }
}

Config::loadBaseConfig(__DIR__ . "/config.json");
