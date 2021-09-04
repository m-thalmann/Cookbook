<?php

namespace API\config;

use PAF\Model\Database;

class ConfigSettings {
    /**
     * @var array Map containing all possible config-paths (key) and meta-data about them (value)
     */
    const SETTINGS = [
        "root_url" => [
            "defaultValue" => "/api",
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "production" => [
            "defaultValue" => true,
            "datatype" => self::TYPE_BOOLEAN,
            "baseConfig" => true,
        ],
        "database.host" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "database.user" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "database.password" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "database.database" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "database.charset" => [
            "defaultValue" => "utf8",
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "image_store" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
            "baseConfig" => true,
        ],
        "token.secret" => [
            "defaultValue" => "86nYFNtuqMxGCG3v7TQuoFhgMSwil3hP",
            "datatype" => self::TYPE_STRING,
        ],
        "token.ttl" => [
            "defaultValue" => 604800,
            "datatype" => self::TYPE_INTEGER,
            "validators" => [
                "min" => 60,
            ],
        ],
        "password.secret" => [
            "defaultValue" => "86nYFNtuqMxGCG3v7TQuoFhgMSwil3hP",
            "datatype" => self::TYPE_STRING,
        ],
        "password.reset_ttl" => [
            "defaultValue" => 600,
            "datatype" => self::TYPE_INTEGER,
            "validators" => [
                "min" => 60,
            ],
        ],
        "registration_enabled" => [
            "defaultValue" => true,
            "datatype" => self::TYPE_BOOLEAN,
        ],
        "email_verification.enabled" => [
            "defaultValue" => false,
            "datatype" => self::TYPE_BOOLEAN,
        ],
        "email_verification.ttl" => [
            "defaultValue" => 3600,
            "datatype" => self::TYPE_INTEGER,
            "validators" => [
                "min" => 60,
            ],
        ],
        "hcaptcha.enabled" => [
            "defaultValue" => false,
            "datatype" => self::TYPE_BOOLEAN,
        ],
        "hcaptcha.secret" => [
            "defaultValue" => "0x0000000000000000000000000000000000000000",
            "datatype" => self::TYPE_STRING,
        ],
        "mail.smtp.host" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
        ],
        "mail.smtp.port" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
        ],
        "mail.smtp.encrypted" => [
            "defaultValue" => true,
            "datatype" => self::TYPE_STRING,
        ],
        "mail.smtp.username" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
        ],
        "mail.smtp.password" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
        ],
        "mail.from.mail" => [
            "defaultValue" => "cookbook@example.com",
            "datatype" => self::TYPE_STRING,
            "validators" => [
                "email" => true,
            ],
        ],
        "mail.from.name" => [
            "defaultValue" => "Cookbook",
            "datatype" => self::TYPE_STRING,
        ],
    ];

    /**
     * @var array Paths that should not be output
     */
    const HIDDEN_PATHS = [
        "database.password",
        "token.secret",
        "password.secret",
        "hcaptcha.secret",
        "mail.smtp.password",
    ];

    const TYPE_STRING = "string";
    const TYPE_BOOLEAN = "boolean";
    const TYPE_INTEGER = "integer";
    const TYPE_NUMBER = "number";

    /**
     * Returns all visible config-paths
     *
     * @return string[] The visible paths
     */
    public static function getVisiblePaths() {
        return array_diff(array_keys(self::SETTINGS), self::HIDDEN_PATHS);
    }

    /**
     * Parses the given value by the given datatype
     *
     * @param string $datatype The datatype of the value
     * @param mixed $value The value to parse
     *
     * @see ConfigSettings::TYPE_STRING
     * @see ConfigSettings::TYPE_BOOLEAN
     * @see ConfigSettings::TYPE_INTEGER
     * @see ConfigSettings::TYPE_NUMBER
     *
     * @return mixed The parsed value
     */
    public static function parseConfigValue($datatype, $value) {
        if ($value !== null) {
            switch ($datatype) {
                case self::TYPE_BOOLEAN:
                    if (!is_bool($value)) {
                        $value = strcmp($value, "true") === 0;
                    }
                    break;
                case self::TYPE_INTEGER:
                    $value = intval($value);
                    break;
                case self::TYPE_NUMBER:
                    $value = floatval($value);
                    break;
                default:
                    $value = $value;
            }
        }

        return $value;
    }

    /**
     * Validates the setting
     *
     * @param string $path The config-path
     * @param mixed $value The value to check
     *
     * @return boolean Whether the setting is valid or not
     */
    private static function isSettingValid($path, $value) {
        $setting = self::SETTINGS[$path];

        if (!array_key_exists("validators", $setting)) {
            return true;
        }

        $validators = $setting["validators"];

        foreach ($validators as $name => $validatorValue) {
            switch ($name) {
                case "min":
                    if ($value < $validatorValue) {
                        return false;
                    }
                    break;
                case "max":
                    if ($value > $validatorValue) {
                        return false;
                    }
                    break;
                case "email":
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Loads the config from the database
     */
    public static function loadConfig() {
        $db = Database::get();

        $stmt = $db->query("SELECT * FROM config");

        if ($stmt === false) {
            throw new \Exception('Config could not be loaded');
        }

        $config = [];

        foreach ($stmt->fetchAll() as $row) {
            $config[$row["key"]] = [
                "key" => $row["key"],
                "value" => self::parseConfigValue(
                    $row["datatype"],
                    $row["value"]
                ),
                "datatype" => $row["datatype"],
            ];
        }

        return $config;
    }

    /**
     * Saves the config value to the database
     *
     * @param string $path The config-path
     * @param mixed $value The value to set
     *
     * @throws \InvalidArgumentException If the path is not editable or the value is not valid
     *
     * @return boolean Whether the value was saved or not
     */
    public static function saveConfigValue($path, $value) {
        if (!array_key_exists($path, self::SETTINGS)) {
            throw new \Exception('Setting not found');
        }

        $setting = self::SETTINGS[$path];

        if (
            array_key_exists("baseConfig", $setting) &&
            $setting["baseConfig"]
        ) {
            throw new \InvalidArgumentException('Setting not editable');
        }

        $db = Database::get();

        $stmt = $db->prepare(
            "INSERT INTO `config` (`key`, `value`, `datatype`) VALUES (:key, :value, :datatype) ON DUPLICATE KEY UPDATE value = :value"
        );

        $value = self::parseConfigValue($setting["datatype"], $value);

        if (!self::isSettingValid($path, $value)) {
            throw new \InvalidArgumentException('Value is not valid');
        }

        if ($value === null) {
            $value = "null";
        } elseif ($setting["datatype"] === self::TYPE_BOOLEAN) {
            $value = $value ? "true" : "false";
        } else {
            $value = strval($value);
        }

        return $stmt->execute([
            "key" => $path,
            "value" => $value,
            "datatype" => $setting["datatype"],
        ]);
    }
}
