<?php

namespace API\config;

use PAF\Model\Database;

class Config {
    const KEYS = [
        "root_url",
        "production",
        "database.host",
        "database.user",
        "database.password",
        "database.database",
        "database.charset",
        "image_store",
        "token.secret",
        "token.ttl",
        "password.secret",
        "password.reset_ttl",
        "registration_enabled",
        "email_verification.enabled",
        "email_verification.ttl",
        "hcaptcha.enabled",
        "hcaptcha.secret",
        "mail.smtp.host",
        "mail.smtp.port",
        "mail.smtp.encrypted",
        "mail.smtp.username",
        "mail.smtp.password",
        "mail.from.mail",
        "mail.from.name",
    ];

    const HIDDEN_KEYS = [
        "database.password",
        "token.secret",
        "password.secret",
        "hcaptcha.secret",
        "mail.smtp.password",
    ];

    private static $baseConfig = null;
    private static $config = null;

    /**
     * Loads the base-config from the json-file
     *
     * @param string $file The path to the json-config-file
     */
    public static function loadBaseConfig($file) {
        if (self::$baseConfig !== null) {
            return;
        }

        self::$baseConfig = @json_decode(file_get_contents($file), true);

        if (self::$baseConfig === null || self::$baseConfig === false) {
            throw new \Exception('Base-Config could not be loaded');
        }
    }

    /**
     * Loads the config from the database
     */
    private static function loadConfig() {
        self::$config = null;

        $db = Database::get();

        $stmt = $db->query("SELECT * FROM config");

        if ($stmt === false) {
            throw new \Exception('Config could not be loaded');
        }

        $config = [];

        foreach ($stmt->fetchAll() as $row) {
            $value = null;

            if ($row["value"] !== null) {
                switch ($row["datatype"]) {
                    case 'boolean':
                        $value = strcmp($row["value"], "true") === 0;
                        break;
                    case 'integer':
                        $value = intval($row["value"]);
                        break;
                    case 'number':
                        $value = floatval($row["value"]);
                        break;
                    default:
                        $value = $row["value"];
                }
            }

            $config[$row["key"]] = [
                "key" => $row["key"],
                "value" => $value,
                "datatype" => $row["datatype"],
            ];
        }

        self::$config = $config;
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
        if (self::$config === null) {
            self::loadConfig();
        }

        if (array_key_exists($path, self::$config)) {
            return self::$config[$path]["value"];
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
}

Config::loadBaseConfig(__DIR__ . "/config.json");
