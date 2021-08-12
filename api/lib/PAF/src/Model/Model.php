<?php

namespace PAF\Model;

/**
 * This abstract class defines a model, that provides functions to
 * store and retrieve items to/from a database.
 *
 * @license MIT
 * @author Matthias Thalmann
 */
abstract class Model implements \JsonSerializable {
    /**
     * @var array The definitions for all models
     *
     * <pre>
     * [<class name>]
     *               ['tablename'] - string - The name of the table in the database
     *               ['deletable'] - bool - Whether the model instances should be deleteable
     *               ['props'] - array - Contains information about all properties for the model
     *                        [<model prop name>]
     *                                           ['name'] - string - The database property name
     *                                           ['type'] - string - The property type; one of: string, integer, float, boolean, json, mixed
     *                                           ['editable'] - bool - Whether the field should be editable
     *                                           ['primary'] - bool - Whether the field is a primary
     *                                           ['nullable'] - bool - Whether the field should be nullable
     *                                           ['extra'] - array - Extra validator properties
     *                                           ['output'] - bool - Whether to output the property in e.g. jsonSerializable or not
     *               ['dbProps'] - array - Maps the database property names (key) to the model property names (value)
     *               ['primaries'] - array - Contains the names of the primary properties
     *               ['autoincrement'] - string|null - Contains the name of the autoincrement property (or null if there is none)
     * </pre>
     */
    private static $definitions = [];

    /**
     * @var array Contains all instances of all models with the key as name of the model-class and the value as array of instances
     */
    private static $instances = [];

    /**
     * @var array A reference to the definition for this model
     */
    private $definition;

    /**
     * @var array The current field values and checksums (md5) of this model instance (value, checksum, original_value, original_checksum)
     */
    private $fields = [];

    /**
     * @var bool $new Whether the model instance is new (not yet saved to the database)
     * @var bool $deleted Whether the instance was delete from the database
     */
    private $new = true;
    private $deleted = false;

    /**
     * Creates an instance of the model and sets the internal fields
     */
    public function __construct() {
        self::init();

        $this->definition = &self::$definitions[static::class];

        foreach (array_keys($this->definition['props']) as $name) {
            if ($this->definition['autoincrement'] === $name) {
                $this->{$name} = null;
            }

            $checksum = md5(json_encode($this->{$name}));

            $this->fields[$name] = [
                "value" => $this->{$name},
                "checksum" => $checksum,
                "original_checksum" => md5(json_encode(null)),
                "original_value" => null, // only if primary
            ];

            unset($this->{$name});
        }

        self::$instances[static::class][spl_object_hash($this)] = $this;
    }

    /**
     * Removes the instance from the instances-array
     */
    public function __destruct() {
        unset(self::$instances[static::class][spl_object_hash($this)]);
    }

    /**
     * Initializes the model by reading all annotations (done only once for each model).
     * Is called by each function that relies on the definition of the model
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    final private static function init() {
        if (array_key_exists(static::class, self::$definitions)) {
            return;
        }

        $ref = new \ReflectionClass(static::class);

        $modelInfo = self::getDocInfo($ref->getDocComment());

        if (empty($modelInfo['tablename'])) {
            throw new \InvalidArgumentException('No tablename supplied');
        }

        $def = [
            "tablename" => $modelInfo['tablename'],
            "deletable" =>
                strtolower(
                    self::getOrDefault($modelInfo, 'deletable', 'true')
                ) === 'true',
            "props" => [],
            "dbProps" => [],
            "primaries" => [],
            "autoincrement" => null,
        ];

        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $propInfo = self::getDocInfo($prop->getDocComment());

            if (
                !array_key_exists('prop', $propInfo) ||
                $prop->class !== static::class
            ) {
                continue;
            }

            $name = $prop->name;

            $type = explode(
                '|',
                explode(
                    ' ',
                    self::getOrDefault($propInfo, 'var', 'mixed'),
                    2
                )[0]
            );
            $nullable = false;

            if (count($type) > 1) {
                $act_type = null;

                foreach ($type as $t) {
                    if ($t === 'null') {
                        $nullable = true;
                        break;
                    } else {
                        $act_type = $t;
                    }
                }

                $type = count($type) === 2 && $nullable ? $act_type : 'mixed';
            } else {
                $type = $type[0];
            }

            $type = strtolower(trim($type));

            $extra = [];

            switch ($type) {
                case 'int':
                    $type = 'integer';
                    break;
                case 'bool':
                    $type = 'boolean';
                    break;
                case 'timestamp':
                    $extra[] = 'timestamp';
                    $type = 'integer';
                    break;
            }

            if (
                !in_array($type, [
                    'string',
                    'integer',
                    'float',
                    'boolean',
                    'json',
                    'mixed',
                ])
            ) {
                throw new \InvalidArgumentException(
                    "Invalid type for property: '$type'"
                );
            }

            if (array_key_exists('enum', $propInfo)) {
                $extra['enum'] = explode(',', $propInfo['enum']);
            }
            if (array_key_exists('email', $propInfo)) {
                $extra[] = 'email';
            }
            if (array_key_exists('url', $propInfo)) {
                $extra[] = 'url';
            }
            if (array_key_exists('ipv4', $propInfo)) {
                $extra[] = 'ipv4';
            }
            if (array_key_exists('ipv6', $propInfo)) {
                $extra[] = 'ipv6';
            }
            if (array_key_exists('ip', $propInfo)) {
                $extra[] = 'ip';
            }

            if (
                array_key_exists('pattern', $propInfo) &&
                !empty($propInfo['pattern']) &&
                $propInfo['pattern'][0] === '/' &&
                $propInfo['pattern'][strlen($propInfo['pattern']) - 1] === '/'
            ) {
                $extra['pattern'] = $propInfo['pattern'];
            }
            if ($type === 'integer' || $type === 'float') {
                if (array_key_exists('min', $propInfo)) {
                    $extra['min'] = floatval($propInfo['min']);
                }
                if (array_key_exists('max', $propInfo)) {
                    $extra['max'] = floatval($propInfo['max']);
                }
                if (array_key_exists('minExclusive', $propInfo)) {
                    $extra['minExclusive'] = floatval(
                        $propInfo['minExclusive']
                    );
                }
                if (array_key_exists('maxExclusive', $propInfo)) {
                    $extra['maxExclusive'] = floatval(
                        $propInfo['maxExclusive']
                    );
                }
            } elseif ($type === 'string') {
                if (array_key_exists('minLength', $propInfo)) {
                    $extra['minLength'] = intval($propInfo['minLength']);
                }
                if (array_key_exists('maxLength', $propInfo)) {
                    $extra['maxLength'] = intval($propInfo['maxLength']);
                }
            }

            $autoincrement = false;

            if (array_key_exists('autoincrement', $propInfo)) {
                if ($def['autoincrement'] !== null) {
                    throw new \InvalidArgumentException(
                        'Only one autoincrement value allowed'
                    );
                }

                if ($nullable) {
                    throw new \InvalidArgumentException(
                        'Autoincrement can\'t be nullable'
                    );
                }

                if ($type !== 'integer') {
                    throw new \InvalidArgumentException(
                        'Type of autoincrement must be integer'
                    );
                }

                $def['autoincrement'] = $name;

                $autoincrement = true;
            }

            $def['props'][$name] = [
                "name" =>
                    $propInfo['prop'] !== null
                        ? $propInfo['prop']
                        : $prop->name,
                "type" => $type,
                "editable" =>
                    strtolower(
                        self::getOrDefault(
                            $propInfo,
                            'editable',
                            !$autoincrement ? 'true' : 'false'
                        )
                    ) === 'true',
                "primary" => array_key_exists('primary', $propInfo),
                "nullable" => $nullable,
                "extra" => $extra,
                "output" =>
                    strtolower(
                        self::getOrDefault($propInfo, 'output', 'true')
                    ) === 'true',
            ];

            $def['dbProps'][$def['props'][$name]['name']] = $name;

            if (array_key_exists('primary', $propInfo)) {
                $def['primaries'][] = $name;

                if ($nullable) {
                    throw new \InvalidArgumentException(
                        'Primary can\'t be nullable'
                    );
                }
                if ($type === 'json') {
                    throw new \InvalidArgumentException(
                        'Type json can\'t be primary'
                    );
                }
            }
        }

        if (count($def['primaries']) === 0) {
            throw new \InvalidArgumentException(
                'At least one primary must be defined'
            );
        }

        self::$definitions[static::class] = $def;

        self::$instances[static::class] = [];
    }

    /**
     * - If called on model: Returns the database instance for all model classes
     * - If called on a model class: Returns the database instance for that model class
     *
     * Example:
     * <code>
     * Model::db();
     * User::db();
     * </code>
     *
     * @see Database::get()
     *
     * @throws \Exception If the database configuration was not yet set
     *
     * @return \PDO
     */
    public static function db() {
        if (static::class === __NAMESPACE__ . '\Model') {
            return Database::get();
        }

        return Database::get(static::class);
    }

    /**
     * If the property exists for this model, the current value is returned
     *
     * @param string $property The name of the property
     *
     * @return mixed The value of the property, if it exists
     */
    public function __get($property) {
        if (array_key_exists($property, $this->fields)) {
            return $this->fields[$property]['value'];
        }
    }

    /**
     * Returns whether the property is set on the model instance (also null)
     *
     * @param string $key The property to check
     *
     * @return bool
     */
    public function __isset($key) {
        return self::hasProperty($key);
    }

    /**
     * Checks whether the model has this property
     *
     * @param string $property The name of the property
     * @param bool $dbProperty Whether to use the name for the properties from the database (true) or from the model (false)
     *
     * @return bool
     */
    final public static function hasProperty($property, $dbProperty = false) {
        self::init();

        if ($dbProperty) {
            foreach (self::$definitions[static::class]['props'] as $prop) {
                if ($prop['name'] === $property) {
                    return true;
                }
            }
        } else {
            return array_key_exists(
                $property,
                self::$definitions[static::class]['props']
            );
        }

        return false;
    }

    /**
     * @see \JsonSerializable
     * @see Model::getProperties()
     *
     * @return array The properties with their values set for this instance (only where output is true)
     */
    public function jsonSerialize() {
        return $this->getProperties(false);
    }

    /**
     * Returns an array with the names of the properties as key an the value as their value
     *
     * @param bool $all Whether to return all properties or only the ones where output is true
     *
     * @return array
     */
    final public function getProperties($all = true) {
        $ret = [];

        foreach ($this->fields as $prop => $field) {
            if ($all || $this->definition['props'][$prop]['output']) {
                $ret[$prop] = $field['value'];
            }
        }

        return $ret;
    }

    /**
     * Sets the value of the property, if it exists on the model.
     *
     * @see Model:editValue()
     *
     * @param string $property The name of the property
     * @param mixed $value The new value for the property
     *
     * @return void
     */
    public function __set($property, $value) {
        $this->editValue($property, $value);
    }

    /**
     * Sets the value for the given property using the __set function
     *
     * @param string $property The name of the property
     * @param mixed $value The value to be set
     * @param bool $convert Whether the value should be converted before setting it (according to the property type)
     *
     * @return $this
     */
    final public function edit($property, $value, $convert = false) {
        if ($convert) {
            $value = self::convertValue($property, $value);
        }
        $this->__set($property, $value);

        return $this;
    }

    /**
     * Sets the supplied values for this instance using the __set function
     *
     * @see Model::edit()
     *
     * @param array $values The values to be set, with the name of the property as key and the value as value
     * @param bool $convert Whether the values should be converted before setting them (according to the property type)
     *
     * @return void
     */
    final public function editValues($values, $convert = false) {
        foreach ($values as $property => $value) {
            $this->edit($property, $value, $convert);
        }
    }

    /**
     * Sets the value for the given property directly
     *
     * @param string $property The name of the property
     * @param mixed $value The value to be set
     * @param bool $convert Whether the value should be converted before setting it (according to the property type)
     * @param bool $ignoreEditable Whether the field should be set, even if it is not editable
     * @param bool $fromDB Whether the value is received from the database or not. If it is, the original value is set aswell.
     *
     * @return $this
     */
    final protected function editValue(
        $property,
        $value,
        $convert = false,
        $ignoreEditable = false,
        $fromDB = false
    ) {
        if (
            array_key_exists($property, $this->definition['props']) &&
            ($ignoreEditable ||
                $this->definition['props'][$property]['editable'])
        ) {
            if ($convert) {
                $value = self::convertValue($property, $value);
            }

            $checksum = md5(json_encode($value));

            if ($checksum !== $this->fields[$property]['checksum']) {
                $this->fields[$property]['value'] = $value;
                $this->fields[$property]['checksum'] = $checksum;
            }

            if ($fromDB) {
                $this->fields[$property]['original_checksum'] = $checksum;

                if ($this->definition['props'][$property]['primary']) {
                    $this->fields[$property]['original_value'] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * This function is used to modify the value of a property, since only setting it is possible normally.
     * The function calls the callable with the value as argument and sets the resulting value.
     *
     * Example:
     * <code>
     * $model->editCallback('propname', function(&$value){ $value[0] = 'test'; });
     * </code>
     *
     * @param string $prop The name of the property
     * @param callable $callable The callable that is called with the value as argument (use reference "&"!); it does not need to return anything
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    final public function editCallback($prop, $callable, $convert = false) {
        if (!self::hasProperty($prop)) {
            return;
        }
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(
                'Second argument must be callable'
            );
        }

        $val = $this->fields[$prop]['value'];

        $callable($val);

        $this->edit($prop, $val, $convert);
    }

    /**
     * Creates an instance of the model from the supplied values. Sets the values using the __set function
     *
     * @see Model::editValues()
     *
     * @param array $values The values to be set, with the name of the property as key and the value as value
     * @param bool $convert Whether the values should be converted before setting them (according to the property type)
     *
     * @return static The created model instance
     */
    final public static function fromValues(array $values, $convert = true) {
        $m = new static();

        $m->editValues($values, $convert);

        return $m;
    }

    /**
     * Creates a collection of model instances for the supplied values. Sets the values using the __set function
     *
     * @see Model::fromValues()
     *
     * @param array $values The values for creating the instances where every element of the array contains a map with keys as property names and values as values for them
     * @param bool $convert Whether the values should be converted before setting them (according to the property type)
     *
     * @return Collection The created collection of model instances
     */
    final public static function collectionFromValues(
        array $values,
        $convert = true
    ) {
        $models = [];

        foreach ($values as $value) {
            $models[] = self::fromValues($value, $convert);
        }

        return new Collection($models);
    }

    /**
     * Creates an instance of the model from the supplied values. Sets the values directly
     *
     * @see Model::editValue()
     *
     * @param array $values The values for creating the instances where every element of the array contains a map with keys as property names and values as values for them
     * @param bool $convert Whether the values should be converted before setting them (according to the property type)
     * @param bool $ignoreEditable Whether the field should be set, even if it is not editable
     * @param bool $fromDB Whether the value is received from the database or not. If it is, the original value is set aswell.
     *
     * @return static The created model instance
     */
    final protected static function create(
        array $values,
        $convert = true,
        $ignoreEditable = false,
        $fromDB = false
    ) {
        $m = new static();

        foreach ($values as $property => $value) {
            $m->editValue(
                $property,
                $value,
                $convert,
                $ignoreEditable,
                $fromDB
            );
        }

        if ($fromDB) {
            $m->new = false;
        }

        return $m;
    }

    /**
     * Creates a collection of model instances for the supplied values. Sets the values directly
     *
     * @see Model::create()
     *
     * @param array $values The values for creating the instances where every element of the array contains a map with keys as property names and values as values for them
     * @param bool $convert Whether the values should be converted before setting them (according to the property type)
     * @param bool $ignoreEditable Whether the field should be set, even if it is not editable
     * @param bool $fromDB Whether the value is received from the database or not. If it is, the original value is set aswell.
     *
     * @return Collection The created collection of model instances
     */
    final protected static function createCollection(
        array $values,
        $convert = true,
        $ignoreEditable = false,
        $fromDB = false
    ) {
        $models = [];

        foreach ($values as $value) {
            $models[] = self::create(
                $value,
                $convert,
                $ignoreEditable,
                $fromDB
            );
        }

        return new Collection($models);
    }

    /**
     * This function is executed for each property when validated (checked as last).
     * It should be overridden by the extending class to add custom validation for fields
     *
     * @see Model::validateProperty()
     *
     * @param string $property The name of the property
     * @param mixed $value The value to be validated
     *
     * @return mixed|null the id of the custom validation error (can by anything); or null if no custom validation error
     */
    protected function customValidateProperty($property, $value) {
        return null;
    }

    /**
     * Validates the given value for the property.
     * If the property is nullable and the value is null, it is automatically valid.
     *
     * @see ValidationError
     *
     * @param string $prop The name of the property
     * @param mixed $value The value to be validated
     *
     * @return ValidationError|null The validation error or null if there was no error
     */
    final public function validateProperty($prop, $value) {
        if (!self::hasProperty($prop)) {
            return new ValidationError(ValidationError::INVALID_PROPERTY);
        }

        $propInfo = $this->definition['props'][$prop];

        if (
            $this->definition['autoincrement'] !== $prop &&
            !$propInfo['nullable'] &&
            $value === null
        ) {
            return new ValidationError(ValidationError::INVALID_NULL);
        }

        if ($value === null) {
            return null;
        }

        switch ($propInfo['type']) {
            case 'string':
                if (!is_string($value)) {
                    return new ValidationError(ValidationError::INVALID_TYPE);
                }
                break;
            case 'integer':
                if (!is_int($value)) {
                    return new ValidationError(ValidationError::INVALID_TYPE);
                }
                break;
            case 'float':
                if (!is_float($value)) {
                    return new ValidationError(ValidationError::INVALID_TYPE);
                }
                break;
            case 'boolean':
                if (!is_bool($value)) {
                    return new ValidationError(ValidationError::INVALID_TYPE);
                }
                break;
            case 'json':
                if (
                    !is_array($value) &&
                    !($value instanceof \JsonSerializable)
                ) {
                    return new ValidationError(ValidationError::INVALID_TYPE);
                }
                break;
        }

        foreach ($propInfo['extra'] as $key => $extra) {
            if (is_int($key)) {
                switch ($extra) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            return new ValidationError(
                                ValidationError::INVALID_EMAIL
                            );
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            return new ValidationError(
                                ValidationError::INVALID_URL
                            );
                        }
                        break;
                    case 'ipv4':
                        if (
                            !filter_var(
                                $value,
                                FILTER_VALIDATE_IP,
                                FILTER_FLAG_IPV4
                            )
                        ) {
                            return new ValidationError(
                                ValidationError::INVALID_IP
                            );
                        }
                        break;
                    case 'ipv6':
                        if (
                            !filter_var(
                                $value,
                                FILTER_VALIDATE_IP,
                                FILTER_FLAG_IPV6
                            )
                        ) {
                            return new ValidationError(
                                ValidationError::INVALID_IP
                            );
                        }
                        break;
                    case 'ip':
                        if (!filter_var($value, FILTER_VALIDATE_IP)) {
                            return new ValidationError(
                                ValidationError::INVALID_IP
                            );
                        }
                        break;
                }
            } else {
                switch ($key) {
                    case 'enum':
                        if (!in_array($value, $extra, true)) {
                            return new ValidationError(
                                ValidationError::INVALID_ENUM
                            );
                        }
                        break;
                    case 'pattern':
                        if (preg_match($extra, $value) !== 1) {
                            return new ValidationError(
                                ValidationError::INVALID_PATTERN
                            );
                        }
                        break;
                    case 'min':
                        if ($value < $extra) {
                            return new ValidationError(
                                ValidationError::INVALID_MIN
                            );
                        }
                        break;
                    case 'max':
                        if ($value > $extra) {
                            return new ValidationError(
                                ValidationError::INVALID_MAX
                            );
                        }
                        break;
                    case 'minExclusive':
                        if ($value <= $extra) {
                            return new ValidationError(
                                ValidationError::INVALID_MIN
                            );
                        }
                        break;
                    case 'maxExclusive':
                        if ($value >= $extra) {
                            return new ValidationError(
                                ValidationError::INVALID_MAX
                            );
                        }
                        break;
                    case 'minLength':
                        if (strlen($value) < $extra) {
                            return new ValidationError(
                                ValidationError::INVALID_MIN
                            );
                        }
                        break;
                    case 'maxLength':
                        if (strlen($value) > $extra) {
                            return new ValidationError(
                                ValidationError::INVALID_MAX
                            );
                        }
                        break;
                }
            }
        }

        $customValidError = $this->customValidateProperty($prop, $value);
        if ($customValidError !== null) {
            return ValidationError::custom($customValidError);
        }

        return null;
    }

    /**
     * Returns the first found validation error of any property
     *
     * @see Model::validateProperty()
     *
     * @return array|null ['property'] - string - name of the property <br/>
     *                    ['error'] - ValidationError - validation error <br/>
     *                    or null if no validation error found <br/>
     */
    final public function getFirstValidationError() {
        foreach ($this->fields as $prop => $cont) {
            $error = $this->validateProperty($prop, $cont['value']);
            if ($error !== null) {
                return [
                    "property" => $prop,
                    "error" => $error,
                ];
            }
        }

        return null;
    }

    /**
     * Returns a map of all validation errors with the property name as key and the value as validation error
     *
     * @see Model::validateProperty()
     *
     * @return array<string, ValidationError> The found validation errors
     */
    final public function getValidationErrors() {
        $errors = [];

        foreach ($this->fields as $prop => $cont) {
            $error = $this->validateProperty($prop, $cont['value']);
            if ($error !== null) {
                $errors[$prop] = $error;
            }
        }

        return $errors;
    }

    /**
     * The first validation error for the property (or null if no error)
     *
     * @see Model::validateProperty()
     *
     * @return ValidationError|null The found validation error
     */
    final public function getValidationError($property) {
        return $this->validateProperty(
            $property,
            self::hasProperty($property)
                ? $this->fields[$property]['value']
                : null
        );
    }

    /**
     * Checks whether the instance/the given property is valid (no validation errors).
     * Checks the instance if the property is not set, otherwise only the property
     *
     * @see Model::validateProperty()
     * @see Model::getFirstValidationError()
     *
     * @param string|null The name of the property
     *
     * @return bool
     */
    final public function isValid($prop = null) {
        if ($prop !== null) {
            if (self::hasProperty($prop)) {
                return $this->validateProperty(
                    $prop,
                    $this->fields[$prop]['value']
                ) === null;
            }
        } else {
            return $this->getFirstValidationError() === null;
        }

        return true;
    }

    /**
     * Checks whether the instance/the given property has changes.
     * Checks the instance if the property is not set, otherwise only the property
     *
     * @param string|null The name of the property
     *
     * @return bool
     */
    final public function hasChanges($prop = null) {
        if ($prop !== null) {
            if (self::hasProperty($prop)) {
                return $this->fields[$prop]['checksum'] !==
                    $this->fields[$prop]['original_checksum'];
            }
        } else {
            foreach (array_keys($this->fields) as $prop) {
                if ($this->hasChanges($prop)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns an array of all changed properties
     *
     * @see Model::hasChanges()
     *
     * @return array The names of the changed properties
     */
    final public function getChanges() {
        $props = [];

        foreach (array_keys($this->fields) as $prop) {
            if ($this->hasChanges($prop)) {
                $props[] = $prop;
            }
        }

        return $props;
    }

    /**
     * Checks whether the instance was deleted from the database
     *
     * @return bool
     */
    final public function isDeleted() {
        return $this->deleted;
    }

    /**
     * Checks whether the instance is new (not yet saved to database)
     *
     * @return bool
     */
    final public function isNew() {
        return $this->new;
    }

    /**
     * Checks whether the instance is deleted; if it is it throws an exception
     *
     * @throws \Exception If the instance was deleted from the database
     *
     * @return void
     */
    final protected function checkDeleted() {
        if ($this->deleted) {
            throw new DeletedException();
        }
    }

    /**
     * Returns the number of rows found for the given where-clause
     *
     * @see https://phpdelusions.net/pdo#prepared
     *
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     *
     * @return int The number of rows found
     */
    final public static function count($whereClause = "1", array $values = []) {
        self::init();

        $stmt = static::db()->prepare(
            "SELECT COUNT(*) AS count FROM " .
                self::$definitions[static::class]['tablename'] .
                " WHERE " .
                $whereClause
        );
        $stmt->execute($values);

        return intval($stmt->fetch()['count']);
    }

    /**
     * Returns a Query-instance for the given where-clause
     *
     * @see https://phpdelusions.net/pdo#prepared
     *
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     *
     * @return Query The created query object
     */
    final public static function query($whereClause = "1", array $values = []) {
        return new Query(static::class, $whereClause, $values);
    }

    /**
     * Returns a collection of model instances for the given where-clause.
     * The result is sorted by the order-clause and limited/offset by the parameters.
     *
     * @see https://phpdelusions.net/pdo#prepared
     *
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     * @param string|null $orderClause Defines how the resulting collection is sorted (sql statement); if null, then it will not be sorted
     * @param int|null $limit Defines how many results will be part of the collection (default no limit)
     * @param int|null $offset Defines how much the results will be offset (default 0)
     *
     * @return Collection
     */
    final public static function get(
        $whereClause = "1",
        array $values = [],
        $orderClause = null,
        $limit = null,
        $offset = null
    ) {
        $stmt = self::getStmt(
            $whereClause,
            $values,
            $orderClause,
            $limit,
            $offset
        );
        $stmt->execute();

        return self::createCollection($stmt->fetchAll(), true, true, true);
    }

    /**
     * Returns an array values returned from the database for the given where-clause.
     * The result is sorted by the order-clause and limited/offset by the parameters.
     *
     * @see https://phpdelusions.net/pdo#prepared
     *
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     * @param string|null $orderClause Defines how the resulting collection is sorted (sql statement); if null, then it will not be sorted
     * @param int|null $limit Defines how many results will be part of the collection (default no limit)
     * @param int|null $offset Defines how much the results will be offset (default 0)
     * @param bool $allProperties Whether to return all properties or only the ones where output is true
     *
     * @return array
     */
    final public static function getRaw(
        $whereClause = "1",
        array $values = [],
        $orderClause = null,
        $limit = null,
        $offset = null,
        $allProperties = true
    ) {
        $stmt = self::getStmt(
            $whereClause,
            $values,
            $orderClause,
            $limit,
            $offset,
            $allProperties
        );
        $stmt->execute();

        $ret = [];

        while ($row = $stmt->fetch()) {
            foreach ($row as $property => $value) {
                $row[$property] = static::convertValue($property, $value);
            }

            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * Creates a PDO prepared statement, using the supplied values.
     * The statement was not yet executed.
     *
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     * @param string|null $orderClause Defines how the resulting collection is sorted (sql statement); if null, then it will not be sorted
     * @param int|null $limit Defines how many results will be part of the collection (default no limit)
     * @param int|null $offset Defines how much the results will be offset (default 0)
     * @param bool $allProperties Whether to return all properties or only the ones where output is true
     *
     * @return \PDOStatement
     */
    private static function getStmt(
        $whereClause,
        array $values = [],
        $orderClause = null,
        $limit = null,
        $offset = null,
        $allProperties = true
    ) {
        self::init();

        $sql =
            "SELECT " .
            self::getSelectClause($allProperties) .
            " FROM " .
            self::$definitions[static::class]['tablename'] .
            " WHERE " .
            $whereClause;

        if ($orderClause !== null) {
            $sql .= " ORDER BY $orderClause";
        }
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        if ($offset !== null) {
            $sql .= " OFFSET $offset";
        }

        $stmt = static::db()->prepare($sql);

        if (count($values) > 0) {
            $assoc = is_string(array_keys($values)[0]);

            foreach ($values as $key => $value) {
                $stmt->bindValue($assoc ? $key : $key + 1, $value);
            }
        }

        return $stmt;
    }

    /**
     * Returns a collection with all results for this model
     *
     * @param string|null $orderClause Defines how the resulting collection is sorted (sql statement); if null, then it will not be sorted
     *
     * @return Collection
     */
    final public static function getAll($orderClause = null) {
        return self::get('1', [], $orderClause);
    }

    /**
     * Deletes the instance from the database
     *
     * @return bool true if it was successful (also if no row deleted), false otherwise
     */
    final public function delete() {
        $where = $this->getWhereClause();

        $ok = self::deleteByQuery($where['sql'], $where['values']);

        if ($ok) {
            $this->deleted = true;
        }

        return $ok;
    }

    /**
     * Deletes the rows from the database by the given where-clause.
     *
     * @see https://phpdelusions.net/pdo#prepared
     *
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     *
     * @return bool true if it was successful (also if no row deleted), false otherwise
     */
    final public static function deleteByQuery(
        $whereClause,
        array $values = []
    ) {
        self::init();

        $stmt = static::db()->prepare(
            "DELETE FROM " .
                self::$definitions[static::class]['tablename'] .
                " WHERE {$whereClause}"
        );

        return $stmt->execute($values);
    }

    /**
     * Removes all entries for this model in the database and resets the autoincrement value
     *
     * @return bool true if it was successful, false otherwise
     */
    final public static function truncate() {
        self::init();

        return !!static::db()->query(
            'TRUNCATE TABLE ' . self::$definitions[static::class]['tablename']
        );
    }

    /**
     * Saves the model instance to the database.
     * If it was not yet saved (= new) a new entry will be created and the autoincrement value (if exists) will be set.
     * If it was saved before, the existing entry will be updated.
     *
     * @param bool $reload Whether the instance should be reloaded from the database after completion or not
     *
     * @throws InvalidException If the model instance is not valid
     * @throws DuplicateException If an unique-constraint fails
     * @throws \PDOException If a different PDO exception is thrown
     *
     * @return bool true if it was successful, false otherwise
     */
    public function save($reload = true) {
        $this->checkDeleted();

        $validationError = $this->getFirstValidationError();

        if ($validationError !== null) {
            throw new InvalidException($validationError);
        }

        $changes = $this->getChanges();

        if (count($changes) == 0) {
            return true;
        }

        $sql = null;

        if ($this->new) {
            $sql = "INSERT INTO {$this->definition['tablename']} ";
        } else {
            $sql = "UPDATE {$this->definition['tablename']} SET ";
        }

        $fields = [];
        $values = [];
        $placeholder = [];

        foreach ($changes as $prop) {
            $field = $this->definition['props'][$prop];

            switch ($field['type']) {
                case 'json':
                    $values[] = json_encode($this->fields[$prop]['value']);
                    break;
                case 'boolean':
                    $values[] = (int) $this->fields[$prop]['value'];
                    break;
                default:
                    $values[] = $this->fields[$prop]['value'];
            }

            if ($this->new) {
                $fields[] = "{$field['name']}";

                if (in_array('timestamp', $field['extra'], true)) {
                    $placeholder[] = "FROM_UNIXTIME(?)";
                } else {
                    $placeholder[] = "?";
                }
            } else {
                $fields[] =
                    "{$field['name']} = " .
                    (in_array('timestamp', $field['extra'], true)
                        ? "FROM_UNIXTIME(?)"
                        : "?");
            }
        }

        if ($this->new) {
            $sql .=
                "(" .
                implode(', ', $fields) .
                ") VALUES (" .
                implode(', ', $placeholder) .
                ")";
        } else {
            $whereClause = $this->getWhereClause();

            $sql .= implode(', ', $fields) . " WHERE " . $whereClause['sql'];

            $values = array_merge($values, $whereClause['values']);
        }

        $stmt = static::db()->prepare($sql);

        try {
            if ($stmt->execute($values) === true) {
                if ($this->definition['autoincrement'] !== null && $this->new) {
                    $id = intval(
                        static::db()->lastInsertId(
                            $this->definition['tablename']
                        )
                    );

                    $this->editValue(
                        $this->definition['autoincrement'],
                        $id,
                        false,
                        true,
                        true
                    );
                }

                foreach ($changes as $prop) {
                    if (in_array($prop, $this->definition['primaries'])) {
                        $value = $this->fields[$prop]['value'];

                        $this->fields[$prop]['original_value'] = $value;
                        $this->fields[$prop]['original_checksum'] = md5(
                            json_encode($value)
                        );
                    }
                }

                $this->new = false;

                return !$reload || $this->reload();
            } else {
                return false;
            }

            return true;
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new DuplicateException($e->errorInfo[2]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Reloads the model instance with the values from the database.
     * Not saved changes will be overwritten!
     *
     * @throws NotSavedException If the model was not yet saved to the database
     *
     * @return bool true if it was successful, false otherwise
     */
    final public function reload() {
        $this->checkDeleted();

        if ($this->new) {
            throw new NotSavedException();
        }

        $whereClause = $this->getWhereClause();

        $sql =
            "SELECT " .
            self::getSelectClause() .
            " FROM {$this->definition['tablename']} WHERE " .
            $whereClause['sql'];

        $stmt = static::db()->prepare($sql);

        if (!$stmt->execute($whereClause['values'])) {
            return false;
        }

        $count = $stmt->rowCount();

        if ($count === 0) {
            $this->deleted = true;
            return false;
        }
        if ($count > 1) {
            // can not happen
            return false;
        }

        $data = $stmt->fetch();

        foreach ($data as $prop => $val) {
            $this->editValue($prop, $val, true, true, true);
        }

        return true;
    }

    /**
     * Reloads all instances of the model on which the function was called
     * or all instances of any model if called on the Model class
     *
     * Example:
     * <code>
     * Model::reloadAll(); // reloads all models
     * User::reloadAll(); // reloads all users
     * </code>
     *
     * @return void
     */
    final static function reloadAll() {
        self::init();
        
        if (static::class === __NAMESPACE__ . "\Model") {
            foreach (self::$instances as $instances) {
                foreach ($instances as $instance) {
                    if (!$instance->isNew()) {
                        $instance->reload();
                    }
                }
            }
        } else {
            foreach (self::$instances[static::class] as $instance) {
                if (!$instance->isNew()) {
                    $instance->reload();
                }
            }
        }
    }

    /**
     * Returns a sql-string containing the columns to select for this model
     *
     * Return example: "user_id AS id, user_name AS name"
     *
     * @param bool $allProperties Whether to return all properties or only the ones where output is true
     *
     * @return string
     */
    private static function getSelectClause($allProperties = true) {
        self::init();

        $fields = [];

        foreach (self::$definitions[static::class]['props'] as $name => $prop) {
            if (
                !$allProperties &&
                !self::$definitions[static::class]['props'][$name]['output']
            ) {
                continue;
            }

            if (in_array('timestamp', $prop['extra'], true)) {
                $fields[] = "UNIX_TIMESTAMP({$prop['name']}) AS $name";
            } else {
                $fields[] = "{$prop['name']} AS $name";
            }

        }
        

        return implode(', ', $fields);
    }

    /**
     * Returns the sql where-clause and values needed to get the current model instance from the database
     * (by using the primary properties)
     *
     * @return array ['sql'] - string - the sql where-clause
     *               ['values'] - array - the values to insert safely (prepared statement)
     */
    private function getWhereClause() {
        self::init();

        $primaries = [];
        $values = [];

        foreach ($this->definition['primaries'] as $primary) {
            if ($this->fields[$primary]['original_value'] !== null) {
                if (
                    in_array(
                        'timestamp',
                        $this->definition['props'][$primary]['extra'],
                        true
                    )
                ) {
                    $primaries[] = "{$this->definition['props'][$primary]['name']} = FROM_UNIXTIME(?)";
                } else {
                    $primaries[] = "{$this->definition['props'][$primary]['name']} = ?";
                }

                $values[] = $this->fields[$primary]['original_value'];
            } else {
                $primaries[] = "{$this->definition['props'][$primary]['name']} IS NULL";
            }
        }

        return [
            "sql" => implode(' AND ', $primaries),
            "values" => $values,
        ];
    }

    /**
     * Converts the given value according to the type of the property
     *
     * @param string $property The name of the property
     * @param mixed $value The value to be converted
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed The converted value
     */
    private static function convertValue($property, $value) {
        self::init();

        if ($value === null) {
            return null;
        }

        if (!self::hasProperty($property)) {
            throw new \InvalidArgumentException('No property with this name');
        }

        switch (self::$definitions[static::class]['props'][$property]['type']) {
            case 'integer':
                if (!is_int($value)) {
                    return intval($value);
                }
                break;
            case 'float':
                if (!is_float($value)) {
                    return floatval($value);
                }
                break;
            case 'boolean':
                if (!is_bool($value)) {
                    $upper = strtoupper($value);

                    if (strcmp($upper, 'TRUE') === 0) {
                        return true;
                    } elseif (strcmp($upper, 'FALSE') === 0) {
                        return false;
                    } else {
                        return boolval($value);
                    }
                }
                break;
            case 'json':
                if (is_string($value)) {
                    if (
                        preg_match(
                            "/^(?:null|true|false|(?:\"|\[|{)(?:.|\s)*|-?\d+(?:\.\d*)?)$/",
                            $value
                        )
                    ) {
                        try {
                            $converted = json_decode(
                                $value,
                                true,
                                512,
                                JSON_THROW_ON_ERROR
                            );

                            $value = $converted;
                        } catch (\Exception $e) {
                        }
                    }
                }
                break;
        }

        return $value;
    }

    /**
     * Helper function to parse annotations out of a docblock
     *
     * @param string $doc The docblock to parse
     *
     * @return array The found annotations with the annotation name as key and the value as value (is null if only annotation exists)
     */
    private static function getDocInfo($doc) {
        $rows = array_slice(explode(PHP_EOL, $doc), 1, -1);

        $info = [];

        foreach ($rows as $row) {
            $row = explode(' ', preg_replace('/^\*( )*/', '', trim($row)), 2);

            if ($row[0][0] === '@') {
                $val = count($row) === 2 ? trim($row[1]) : null;

                $info[substr($row[0], 1)] = strlen($val) > 0 ? $val : null;
            }
        }

        return $info;
    }

    /**
     * Helper function that checks an array for a key:
     * if the key exists, it returns the value, otherwise the default value is returned
     *
     * @param array $arr The array to search
     * @param string $key The key to find
     * @param mixed $default The default value to use, if the key is not found
     *
     * @return mixed
     */
    private static function getOrDefault($arr, $key, $default) {
        if (array_key_exists($key, $arr) && $arr[$key] !== null) {
            return $arr[$key];
        }

        return $default;
    }
}
