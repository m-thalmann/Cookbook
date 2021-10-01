<?php

namespace PAF\Model;

/**
 * This class provides functionalities to connect to databases
 * used by the models
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class Database {
    /**
     * @var callable $provider The default PDO-connection provider. If called has to return a PDO instance
     * @var array<string, callable> $modelProviders The key is the name of a model-class, the value is the PDO-connection provider. If called has to return a PDO instance
     */
    private static $provider = null;
    private static $modelProviders = [];

    /**
     * Registers a PDO-connection provider for the model-class, if it is set,
     * or for all model classes (as default provider), if no model-class is set.
     *
     * @param callable $provider The provider function. If called has to return a PDO instance
     * @param string|null $modelClass The name of the model-class for which this database instance should be used
     *
     * @throws \InvalidArgumentException If the model-class was not found or does not implement PAF\Model\Model
     * @throws \Exception If a provider was already set for the model-class (or the default provider was already set)
     *
     * @return void
     */
    public static function registerProvider(
        callable $provider,
        $modelClass = null
    ) {
        if ($modelClass === null) {
            if (self::$provider !== null) {
                throw new \Exception('Provider already set');
            }

            self::$provider = $provider;
        } else {
            self::validateModelClass($modelClass);

            if (array_key_exists($modelClass, self::$modelProviders)) {
                throw new \Exception('Provider already set');
            }

            self::$modelProviders[$modelClass] = $provider;
        }
    }

    /**
     * Unregisters a provider for the model-class, if it is set,
     * or for all model classes (as default provider), if no model-class is set.
     * 
     * @param string|null $modelClass The name of the model-class for which this database instance should be unregistered
     * 
     * @return void
     */
    public static function unregisterProvider($modelClass = null){
        if ($modelClass === null) {
            self::$provider = null;
        } else {
            self::validateModelClass($modelClass);

            if (array_key_exists($modelClass, self::$modelProviders)) {
                unset(self::$modelProviders[$modelClass]);
            }
        }
    }

    /**
     * Sets the provider using a PDO database connection string (DSN)
     *
     * The PDO instance will have the following options set:
     * - DEFAULT_FETCH_MODE: PDO::FETCH_ASSOC
     * - ERRMODE: PDO::ERRMODE_EXCEPTION
     *
     * @link https://www.php.net/manual/de/pdo.construct.php#refsect1-pdo.construct-parameters
     * @link https://www.php.net/manual/de/pdo.drivers.php
     *
     * @see Database::registerProvider()
     *
     * @param string $dsn The PDO connection string
     * @param string|null $user The database user
     * @param string|null $password The user password
     * @param string|null $modelClass The name of the model-class for which this database instance should be used
     *
     * @throws \InvalidArgumentException If the model-class was not found or does not implement PAF\Model\Model
     * @throws \Exception If a provider was already set for the model-class (or the default provider was already set)
     *
     * @return void
     */
    final public static function setDatabaseDSN(
        $dsn,
        $user = null,
        $password = null,
        $modelClass = null
    ) {
        if (
            substr($dsn, 0, 6) === 'sqlite' &&
            substr($dsn, 7, 8) !== ':memory:'
        ) {
            $realpath = realpath(substr($dsn, 7));

            if ($realpath !== false) {
                $dsn = 'sqlite:' . $realpath;
            }
        }

        $provider = function () use ($dsn, $user, $password) {
            static $db = null;

            if ($db === null) {
                $db = new \PDO($dsn, $user, $password, [
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
            }

            return $db;
        };

        self::registerProvider($provider, $modelClass);
    }

    /**
     * Sets the database used by the models by building a DSN
     *
     * @see Database::setDatabaseDSN()
     *
     * @param string $driver The PDO driver (e.g mysql)
     * @param string $host The database host (ip or domain)
     * @param string $database The name of the database
     * @param string $user The database user
     * @param string $password The user password
     * @param string $charset The charset used by the database
     * @param int|null $port The port used by the server (if null, the default port is used)
     * @param string|null $modelClass The name of the model-class for which this database instance should be used
     *
     * @throws \InvalidArgumentException If the model-class was not found or does not implement PAF\Model\Model
     * @throws \Exception If a provider was already set for the model-class (or the default provider was already set)
     *
     * @return void
     */
    final public static function setDatabase(
        $driver,
        $host,
        $database,
        $user = null,
        $password = null,
        $charset = 'utf8',
        $port = null,
        $modelClass = null
    ) {
        $dsn = "$driver:host=$host;dbname=$database;charset=$charset";

        if ($port !== null) {
            $dsn .= ";port=$port";
        }

        self::setDatabaseDSN($dsn, $user, $password, $modelClass);
    }

    /**
     * Returns a pdo instance by calling the provider function. If the connection was not yet initialized,
     * it will be done here (so only one instance exists)
     *
     * @throws \Exception If the database configuration was not yet set
     *
     * @return \PDO The pdo instance
     */
    public static function get($modelClass = null) {
        if ($modelClass !== null) {
            self::validateModelClass($modelClass);

            if (array_key_exists($modelClass, self::$modelProviders)) {
                return self::$modelProviders[$modelClass];
            }
        }

        if (self::$provider !== null) {
            return call_user_func(self::$provider);
        }

        throw new \Exception("No database provider set");
    }

    /**
     * Checks whether the model-class exists and if it implements the
     * PAF\Model\Model class
     *
     * @param string|null $modelClass The name of the model-class for which this database instance should be used
     *
     * @throws \InvalidArgumentException If the model-class was not found or does not implement PAF\Model\Model
     *
     * @return void
     */
    private static function validateModelClass($modelClass) {
        if (!is_string($modelClass) || !class_exists($modelClass)) {
            throw new \InvalidArgumentException('Invalid classname');
        }
        if (!in_array(__NAMESPACE__ . '\\Model', class_parents($modelClass))) {
            throw new \InvalidArgumentException(
                "Invalid class: Must implement '" . __NAMESPACE__ . "\\Model'"
            );
        }
    }
}
