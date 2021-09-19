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
            "encrypted" => true,
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
            "encrypted" => true,
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
            "encrypted" => true,
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
            "datatype" => self::TYPE_BOOLEAN,
        ],
        "mail.smtp.username" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
        ],
        "mail.smtp.password" => [
            "defaultValue" => null,
            "datatype" => self::TYPE_STRING,
            "encrypted" => true,
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

    private const ENCRYPTION_ALGORITHM = 'BF-CBC';

    private static $configSecret = null;

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
     * @param bool $encrypted Whether the value is encrypted or not
     *
     * @see ConfigSettings::TYPE_STRING
     * @see ConfigSettings::TYPE_BOOLEAN
     * @see ConfigSettings::TYPE_INTEGER
     * @see ConfigSettings::TYPE_NUMBER
     *
     * @return mixed The parsed value
     */
    public static function parseConfigValue(
        $datatype,
        $value,
        $encrypted = false
    ) {
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

        if ($encrypted) {
            $value = self::decryptValue($value);
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
     * Returns the configuration secret
     *
     * @see config_secret (file)
     *
     * @return string The config-secret
     */
    private static function getConfigSecret() {
        if (self::$configSecret === null) {
            $configSecret = @file_get_contents(__DIR__ . "/config_secret");

            if ($configSecret !== false) {
                self::$configSecret = trim($configSecret);
            }
        }

        return self::$configSecret;
    }

    /**
     * Encrypts a config-value using the config-secret
     *
     * @param string $value The value to encrypt
     *
     * @see ConfigSettings::getConfigSecret
     * @see ConfigSettings::ENCRYPTION_ALGORITHM
     *
     * @return string the base64encoded iv & encrypted value
     */
    private static function encryptValue($value) {
        $ivLength = @openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM);

        if ($ivLength === false) {
            return $value;
        }

        $iv = random_bytes($ivLength);

        return base64_encode(
            $iv .
                openssl_encrypt(
                    $value,
                    self::ENCRYPTION_ALGORITHM,
                    self::getConfigSecret(),
                    OPENSSL_RAW_DATA,
                    $iv
                )
        );
    }

    /**
     * Decrypts a config-value using the config-secret
     *
     * @param string $value The base64encoded iv & encrypted value to decrypt
     *
     * @see ConfigSettings::getConfigSecret
     * @see ConfigSettings::ENCRYPTION_ALGORITHM
     *
     * @return string the decrypted value
     */
    private static function decryptValue($value) {
        $ivLength = @openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM);

        if ($ivLength === false) {
            return $value;
        }

        $value = base64_decode($value);

        $iv = substr($value, 0, $ivLength);

        return openssl_decrypt(
            substr($value, $ivLength),
            self::ENCRYPTION_ALGORITHM,
            self::getConfigSecret(),
            OPENSSL_RAW_DATA,
            $iv
        );
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
            $datatype = $row["datatype"];
            $encrypted = false;

            if (array_key_exists($row["key"], self::SETTINGS)) {
                $setting = self::SETTINGS[$row["key"]];

                $datatype = $setting["datatype"];
                $encrypted = $setting["encrypted"] ?? false;
            }

            $config[$row["key"]] = [
                "key" => $row["key"],
                "value" => $row["value"],
                "datatype" => $datatype,
                "encrypted" => $encrypted,
                "parsed" => false,
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
            "INSERT INTO `config` (`key`, `value`, `datatype`) VALUES (:key, :value, :datatype) ON DUPLICATE KEY UPDATE `value` = :value, `datatype` = :datatype"
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
            if ($setting["encrypted"] ?? false) {
                $value = self::encryptValue($value);
            }

            $value = strval($value);
        }

        return $stmt->execute([
            "key" => $path,
            "value" => $value,
            "datatype" => $setting["datatype"],
        ]);
    }
}
