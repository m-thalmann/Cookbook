# PAF\Model

This model is used to map database entries to PHP objects

**INFO:** Currently the functionalities have **only** been tested using **MySQL**!

[Documentation](https://m-thalmann.github.io/PAF/namespaces/paf-model.html)

## Table of contents

-   [Quick start](#quick-start)
-   [Classes](#classes)
    -   [Database](#database)
    -   [Model](#model)
        -   [Class definition](#class-definition)
        -   [Property definition](#property-definition)
            -   [Annotations](#annotations)
        -   [Actions](#actions)
            -   [Creation](#creation)
            -   [Editing values](#editing-values)
            -   [Deleting](#deleting)
            -   [Saving](#saving)
        -   [Validation](#validation)
        -   [Changes](#changes)
        -   [Retrieving items from the database](#retrieving-items-from-the-database)
        -   [Reloading](#reloading)
        -   [Using with router](#using-with-router)
    -   [Collection](#collection)
    -   [Query](#query)
    -   [PaginationResult](#paginationresult)
-   [Exceptions](#exceptions)

<hr>

## Quick start

`models/User.php`:

```php
use PAF\Model\Model;

/**
 * @tablename users
 */
class User extends Model {
    /**
     * @prop
     * @primary
     * @autoincrement
     * @var integer
     */
    public $id;

    /**
     * @prop
     * @var string
     */
    public $username;

    /**
     * @prop
     * @var string
     * @output false
     */
    public $password;

    /**
     * This function is optional
     *
     * Here it forces the password to be encrypted when set
     */
    public function __set($property, $value) {
        switch ($property) {
            case 'password':
                $value = md5($value);
                break;
        }
        parent::__set($property, $value);
    }
}
```

`index.php`:

```php
use PAF\Model\Database;

require_once __DIR__ . '/models/Users.php';

Database::setDatabase(
    'mysql',
    'example.com',
    'database_name',
    'username',
    'password'
);

// List the usernames of all users
foreach (User::getAll() as $user) {
    echo $user->username . '<br/>';
}

// Create and store a new user
$user = new User();
$user->username = 'new_user';
$user->password = '123';

$user->save();

echo $user->id;

// Search users by username
$usersByUsername = User::get('username LIKE :username', [
    ':username' => '%user%',
]);

// Set their passwords
$usersByUsername->edit('password', 'new_password');
$usersByUsername->save();

// Return a router response
$res = User::query()->getResponse();
// ... or
$res = User::query()->getRawResponse(); // no model instances, only values
```

<hr>

## Classes

| Class                        | Documentation                                                                    |
| ---------------------------- | -------------------------------------------------------------------------------- |
| `PAF\Model\Model`            | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Model-Model.html)            |
| `PAF\Model\Database`         | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Model-Database.html)         |
| `PAF\Model\Collection`       | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Model-Collection.html)       |
| `PAF\Model\Query`            | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Model-Query.html)            |
| `PAF\Model\PaginationResult` | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Model-PaginationResult.html) |
| `PAF\Model\ValidationError`  | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Model-ValidationError.html)  |

<hr>

### Database

**Warning:** Currently the functionalities have **only** been tested using **MySQL**!

Before using the models, you need to set a database connection (PDO). There are three functions to do so:

```php
Database::setDatabase(
    $driver,
    $host,
    $database,
    $user,
    $password,
    $charset = 'utf8',
    $port = null, // if not set, the default port is used
    $modelClass = null // if not set, it is set as default connection
);

Database::setDatabaseDSN(
    $dsn,
    $user,
    $password,
    $modelClass = null // if not set, it is set as default connection
);

Database::registerProvider(
    callable $provider,
    $modelClass = null // if not set, it is set as default connection
);
```

#### Examples

```php
Database::setDatabase(
    'mysql',
    'example.com',
    'user_database',
    'root',
    '123',
    'utf8',
    null, // the default port is used
    User::class // set for the User model
);

Database::setDatabaseDSN(
    'mysql:host=example.org;dbname=my_database;charset=utf8',
    'username',
    'passwd',
); // is set as default connection

Database::registerProvider(
    function(){
        $db = new PDO(...);

        // initialize...

        return $db;
    },
    Book::class // set for the Book model
);
```

#### Accessing the database

-   The database connection will only be created once per defined DSN/provider/connection information (the callback function is only called once).

-   You can access it by calling:

    ```php
    // returns the default PDO instance
    Database::get();
    Model::db();

    // returns the PDO instance for the User model
    Database::get(User::class);
    User::db();
    ```

-   If a model class has no defined database connection, the default connection will be used.

-   Since there is only one instance you can simply use transactions (not all drivers support them) and other built-in functions provided by PDO:

    ```php
    Database::get()->beginTransaction();

    // do stuff

    if ($ok) {
        Database::get()->commit();
    } else {
        Database::get()->rollback();
    }
    ```

### Model

To create your own models you need to extend the `Model` class. It is only possible to directly extend the `Model` class (not extend an extended class).

#### Class definition

Your class should have the following structure:

```php
/**
 * @tablename <name of the table in the database>
 * @deletable <true|false>
 */
class <model name> extends Model{
    // properties and functions...
}
```

-   The `tablename` is mandatory
-   The `deletable` annotation is optional; it defines whether the items should be deletable or not (default true)

#### Property definition

A model class has to contain:

-   at least one `primary` property
-   maximum one `autoincrement`

A property has the following structure:

```php
/**
 * @prop [<database property name>]
 * @primary
 * @autoincrement
 * @editable <true|false>
 * @output <true|false>
 * @var <type>[|null] [<description>]
 */
public $propertyName = null; // you can set a default value
```

For a variable to be a valid property it has to have the following characteristics:

-   The annotation `@prop`
-   The variable must be `public`

##### Annotations

**Normal annotations**
| Annotation | Parameter | Default parameter | Description | Example |
|-|-|-|-|-|
| @prop | - or \<table column for property\> | - | Defines that this variable is a property and sets the column name for this property (if empty the name of the variable is used) | @prop user_username |
| @primary | - | - | Defines that this property is a primary key | @primary |
| @autoincrement | - | - | Defines that this property is automatically set by the database; has to have type integer | @autoincrement |
| @editable | bool | true | Sets whether the property should be editable | @editable false |
| @output | bool | true | Sets whether this property should be output when converted to json | @output false |
| @var | \<string\|integer\|float\|boolean\|timestamp\|json\|mixed\>[\|null] | mixed | Sets the type of the property and if it is nullable | @var string |

_timestamp-type:_ the value will be an integer (unix timestamp in seconds) but stored as timestamp/datetime to the database

**Validation annotations**
| Annotation | Parameter | Description | Validation error | Example |
|-|-|-|-|-|
| @enum | \<string\>[,\<string\>] | The value must be contained in the comma-separated list | `ValidationError::INVALID_ENUM` | @enum admin,user |
| @email | - | The value must be a valid email | `ValidationError::INVALID_EMAIL` | @email |
| @url | - | The value must be a valid url | `ValidationError::INVALID_URL` | @url |
| @ipv4 | - | The value must be a valid IPv4 address | `ValidationError::INVALID_IP` | @ipv4 |
| @ipv6 | - | The value must be a valid IPv6 address | `ValidationError::INVALID_IP` | @ipv6 |
| @ip | - | The value must be a valid IPv4 or IPv6 address | `ValidationError::INVALID_IP` | @ip |
| @pattern | /\<regex pattern\>/ | The value must match the given regex pattern | `ValidationError::INVALID_PATTERN` | @pattern /^user.\*\$/ |
| @min | \<int\> | The value must be >= the given value | `ValidationError::INVALID_MIN` | @min 0 |
| @max | \<int\> | The value must be <= the given value | `ValidationError::INVALID_MAX` | @max 255 |
| @minExclusive | \<int\> | The value must be > the given value | `ValidationError::INVALID_MIN` | @minExclusive 100 |
| @maxExclusive | \<int\> | The value must be < the given value | `ValidationError::INVALID_MAX` | @maxExclusive 1000 |
| @minLength | \<int\> | The length of the string has to be >= the given value | `ValidationError::INVALID_MIN` | @minLength 12 |
| @maxLength | \<int\> | The length of the string has to be <= the given value | `ValidationError::INVALID_MAX` | @maxLength 16 |

#### Actions

##### Creation

**INFO:** When creating items, you need to call the `save()` function to save them to the database.

You can create a new item by calling the constructor; you then can set values and save the item to the database:

```php
$newUser = new User();

$newUser->username = 'username';
$newUser->password = '123';

$newUser->save();
```

You can also create a new item and directly set the values:

```php
$newUser = User::fromValues([
    'username' => 'username',
    'password' => 'password',
]);

$newUser->save();
```

If you dont want the values to be converted to the appropriate values you have to pass a second argument:

```php
$newUser = User::fromValues([...], false);

$newUser->save();
```

You can also create multiple items and set their values:

```php
$newUsers = User::collectionFromValues([
    [
        'username' => 'user1',
        'password' => 'password',
    ],
    [
        'username' => 'user2',
        'password' => 'password',
    ],
    [
        'username' => 'user3',
        'password' => 'password',
    ],
]); // returns a collection of users

$newUsers->save();
```

You can also pass the conversion argument here.

##### Editing values

**INFO:** When editing values, you need to call the `save()` function to save the changes to the database.

You can edit values like shown (they will be automatically converted according to the property type):

```php
$user->username = 'new_user';
```

If you want to edit a value, but not automatically convert it, use the following:

```php
$user->edit(
    $property, // the name of the property
    $value, // the new value
    false
);
```

You can also edit multiple values:

```php
$user->editValues(
    [
        'username' => 'new_user',
        'password' => 'new_password',
        // ...
    ],
    $convert // whether to convert the value according to the property type or not
);
```

If you want to modify a property (not replacing it), you have to use the `editCallback` function:

```php
$lib->books[] = 'New book'; // NOT possible!

$lib->books = array_merge($lib->books, ['New book']); // possible

// better
$lib->editCallback('books', function (&$books) {
    // you need to use a reference (&)!
    $books[] = 'New book';
}); // you can also pass the convert parameter (default is false)
```

If you want to set a value, that is not editable, you have to do that within the class:

```php
class User extends Model {
    // ...

    public function setId($id) {
        $this->editValue(
            'id',
            $id,
            true, // whether to convert the value according to the property type or not
            true // whether to ignore the constraint that the property is not editable or not
        );
    }
}
```

If you want to handle setting of new values you have to override the `__set` function,
but make sure to call the `__set` function of the parent:

```php
class User extends Model {
    // ...

    public function __set($property, $value) {
        if ($property === "password") {
            $values = md5($value);
        }

        parent::__set($property, $value);
    }
}
```

##### Deleting

To delete an item from the database do the following:

```php
$user->delete();
```

**WARNING:** This does _not_ require to call the `save()` function

You can also delete items using a query:

```php
User::deleteByQuery('username LIKE :username', [':username' => '%user%']);
```

You can check if an item was deleted:

```php
var_dump($user->isDeleted());
```

You can also delete all items (= truncate):

```php
User::truncate();
```

##### Saving

When calling the `save()` function the item will be:

-   stored to the database, if it was not yet saved
-   updated in the database, if it was stored before

When saving a new item, the autoincrement value (if present) will be set inside of the model:

```php
$u = User::fromValues([...]);
$u->save();

echo $u->id; // prints the actual id in the database
```

You can check if the item was stored to the database before:

```php
echo $u->isNew(); // false if it was stored before
```

If you want to just save an item, but don't need to reload the values from the database, it is highly recommended to set the reload-parameter to false (to increase performance):

```php
$u->save(false);
```

#### Validation

As seen [above](#annotations), there are special annotations for validation. You can check if a model is valid by calling `isValid()`, but there are also other functions for validation:

```php
$user->isValid(); // checks the whole model (returns boolean)
$user->isValid('username'); // checks only the username property (returns boolean)

$error = $user->validateProperty('username', 'value'); // checks, if the supplied value would be valid for the property. If it isn't, it returns a ValidationError object (otherwise null)

$errorUsername = $user->getValidationError('username'); // returns the first validation error for the property

$errors = $user->getValidationErrors(); // returns a map with the key as property-name and the value as ValidationError for each invalid property

$firstError = $user->getFirstValidationError(); // returns the first found validation error in any property

if ($firstError !== null) {
    $errorProperty = $firstError['property'];
    $errorError = $firstError['error'];
}
```

The error from a `ValidationError` corresponds to the "Validation error" column in the [annotations section](#annotations). To retrieve it use the following function:

```php
$e = $error->getError();

switch ($e) {
    case ValidationError::INVALID_MIN:
    case ValidationError::INVALID_MAX:
        echo 'Invalid length';
        break;
    // ...
}
```

There are four other validation errors not mentioned in the table above:

-   `ValidationError::INVALID_PROPERTY` this property does not exist
-   `ValidationError::INVALID_NULL` the value of the property is null, but it is not nullable
-   `ValidationError::INVALID_TYPE` the type of the value and the type of the property do not match
-   `ValidationError::INVALID_CUSTOM` a custom validation error occured (see below)

If you want to add further validation to a property, you have to override the `customValidateProperty()`-function:

```php
class User extends Model {
    // ...

    protected function customValidateProperty($property, $value) {
        switch ($property) {
            case 'username':
                if (strlen($value) < 10 && $value[0] !== 'a') {
                    return 'UsernameError'; // return anything !== null
                }
                break;
        }

        return null;
    }
}

// ...

$error = $user->getValidationError('username');

switch ($error) {
    // ...
    case ValidationError::INVALID_CUSTOM:
        $customError = $error->getCustomError(); // returns the custom error returned by the customValidateProperty()-function

        // ...

        break;
}

// other possibility
if ($error->isCustom()) {
    $customError = $error->getCustomError();
}
```

#### Changes

You can check whether a model has changes (needs to be saved) by calling `hasChanges()`, but there are also other functions to check for changes:

```php
$user->hasChanges(); // checks the whole model (returns boolean)
$user->hasChanges('username'); // checks only the username property (returns boolean)

$changes = $user->getChanges(); // returns an array of property-names that have been changed

$user->isDelete(); // returns whether the model was deleted from the database or not
$user->isNew(); // returns whether the model was saved to / retrieved from the database before or not
```

#### Retrieving items from the database

There are several functions to retrieve items from the database:

```php
$items = User::get(
    "username = :username", // PDO prepared statement
    [":username" => "myuser"]
); // returns a collection of user models

$itemsRaw = User::getRaw(
    "username = :username", // PDO prepared statement
    [":username" => "myuser"]
); // returns an array of maps containing only the values of the properties (no object)

$itemsQuery = User::query(
    "username = :username", // PDO prepared statement
    [":username" => "myuser"]
);

// return responses, where the http-code is 404, if the amount of elements is 0, otherwise it is set to 200
$itemsResponse = $itemsQuery->getResponse(); // response value contains collection of models
$itemsRawResponse = $itemsQuery->getResponseRaw(); // response value contains array of maps containing only the values of the properties (no object)

$allUsers = User::getAll(); // collection of models
```

**Warning:** When using `NULL` in the query you have to use `IS NULL`, since `= NULL` does not work in SQL.

If you want to count, how many rows a query would return, use the `count()` function:

```php
User::count("username LIKE :username", [":username" => "a%"]);
```

For more information on the `query()` function see the [query section](#query) below.

You can add functions to your model to retrieve items:

```php
class User extends Model {
    // ...

    public static function getById($id) {
        return User::get("id = :id", [":id" => $id]);
    }

    public static function searchByUsername($username) {
        return User::get("username LIKE :username", [
            ":username" => "%$username%",
        ]);
    }

    // ...
}
```

#### Reloading

If you want to reload a model from the database or all models use the following:

```php
$user->reload(); // reloads the $user model

User::reloadAll(); // reloads all User models

Model::reloadAll(); // reloads all models
```

#### Using with router

The model is designed to be used with the router component of PAF. You can simply return a model or a collection of models and the output will be converted to json.

You can hide properties using the `@output false` annotation.

If you want to handle the conversion to json yourself you have to override the `jsonSerialize()` function:

```php
class User extends Model {
    // ...

    public function jsonSerialize() {
        $properties = $this->getProperties(false);

        $properties['id'] = "uid_" . $properties['id'];

        return $properties;
    }
}
```

<hr>

### Collection

The collection class is used to store multiple model instances and to be able to modify them all at once.
Normally a collection instance is returned by the `query()`-, `get()`- and `collectionFromValues()`-functions:

```php
$userCollection = User::getAll();
```

You can iterate over the items, get a specific one, count the items, return a response object and return the items as array:

```php
foreach ($userCollection as $user) {
    echo $user->id . "<br/>";
}

// these two are identical
$user = $userCollection->get(0);
$firstUser = $userCollection->getFirst();

// these two are identical
$amount = count($userCollection);
$amountFn = $userCollection->count();

$response = $userCollection->getResponse();

$userArray = $userCollection->toArray();
```

You can also modify the items:

```php
$userCollection->editValues(...); // same as in $user->editValues()
$userCollection->edit(...); // same as in $user->edit()
$userCollection->delete(); // it is recommended to use the deleteByQuery()-function of the model, if you just want to delete items by a query
$userCollection->save(
    $reload = true // whether to reload the models after saving them
);
```

See [above](#editing-values) for more information on the `editValues()` and `edit()` functions.

**INFO:** When editing values, you need to call the `save()` function to save the changes to the database.

**INFO:** The collection can be converted to json using the `json_encode()` function and therefore it can be simply returned as result of a route.

<hr>

### Query

The query object is used to retrieve items from the database by building a query.
It is created by calling the `query()`-function on a model class:

```php
$query = User::query("id > :id", [":id" => 2]);
```

You can then specify your query even further by calling the following functions (they are all optional):

```php
$query
    ->orderBy("username", "asc") // use 'asc' or 'desc'
    ->orderBy("id", "asc") // fallback ordering
    ->limit(5)
    ->offset(1);
```

There are several functions for the resulting items:

```php
$amount = $query->count(); // returns the number of found items
$query->delete(); // deletes the found items (using the deleteByQuery()-function)

$items = $query->get(); // returns a collection of user models
$itemsRaw = $query->getRaw(); // returns an array of maps containing only the values of the properties (no object)

$response = $query->getResponse(); // returns a response with the value as collection of user models
$rawResponse = $query->getRawResponse(); // returns a response with the value as an array of maps containing only the values of the properties (no object)

// page is 0-indexed; pagination resets the limit and offset values of the query
$pagination = $query->pagination($itemsPerPage, $page); // returns a PaginationResult (see below)
$paginationRaw = $query->paginationRaw($itemsPerPage, $page); // returns a PaginationResult (see below)
```

### PaginationResult

This class encapsulates the result of a pagination request.
You can retrieve the result like shown:

```php
$collection = $query->pagination(...)->get();
$response = $query->pagination(...)->getResponse();
```

**INFO:** The pagination result can be converted to json using the `json_encode()` function and therefore it can be simply returned as result of a route.

<hr>

## Exceptions

| Class                          | Description                                                                                |
| ------------------------------ | ------------------------------------------------------------------------------------------ |
| `PAF\Model\DeletedException`   | This exception is thrown, if a model is saved or reloaded after it was deleted             |
| `PAF\Model\DuplicateException` | This exception is thrown, if a model is saved and an unique-constraint fails               |
| `PAF\Model\InvalidException`   | This exception is thrown, if a model is saved but it is invalid                            |
| `PAF\Model\NotSavedException`  | This exception is thrown, if a model is reloaded, but it was not yet saved to the database |
