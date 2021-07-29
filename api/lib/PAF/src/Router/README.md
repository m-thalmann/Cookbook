# PAF\Router

This router is used to create the API routing structure and outputting the result.<br>
It was inspired by [AltoRouter](https://github.com/dannyvankooten/AltoRouter) and [ExpressJS](https://expressjs.com/)

[Documentation](https://m-thalmann.github.io/PAF/namespaces/paf-router.html)

## Table of contents

-   [Setting up](#setting-up)
-   [Quick start](#quick-start)
-   [Classes](#classes)
-   [Initialization](#initialization)
    -   [Headers](#headers)
    -   [Ignore Query](#ignore-query)
-   [Adding routes](#adding-routes)
    -   [Path](#path)
    -   [Targets](#targets)
        -   [Request](#request)
-   [Groups](#groups)
    -   [Lazy groups](#lazy-groups)
-   [Response](#response)
-   [Output](#output)
-   [Examples](#examples)

<hr>

## Setting up

1. Follow the setup-guide of the main repo-README
2. If you are using a Apache webserver, you have to route all requests, that can not be found on the server, to your `index.php` file with a `.htaccess` file. Either use the one provided in this folder or add the following lines to your own:

    ```apacheconf
    RewriteEngine On

    RewriteCond %{HTTP:Authorization} ^(.*)
    RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]

    RewriteCond %{REQUEST_FILENAME}  -f [OR]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [L,QSA]
    ```

    The same is also possible if you are using a different webserver, but in different ways.

3. Now you are ready to go

<hr>

## Quick start

`index.php`:

```php
<?php

// TODO: import the autoloader or the necessary classes

use PAF\Router\Router;

Router::init('/api'); // initialize the router

// add routes to '/api' (base path)
Router::addRoutes()->get('/', function ($req) {
    return [
        "info" => "PAF test-api Version 1.0",
    ];
});

// add routes to '/api/users' (base path + group path)
Router::group('/users')
    ->get('/', function ($req) {
        // load users from DB

        if ($error !== null) {
            return Response::error($error); // 500
        } else {
            return Response::ok($users); // 200
        }
    })
    ->get('/{{i:id}}', function ($req) {
        $id = $req['params']['id']; // converted to int

        // load user from DB

        if ($error !== null) {
            return Response::error($error); // 500
        } else {
            return Response::ok($user); // 200
        }
    });

Router::execute(); // start the path and request-method matching
// returns a boolean, whether a route was found or not
```

This example contains three routes: `GET /api`, `GET /api/users` and `GET /api/users/{id}`.

-   By requesting `GET /api` the following JSON-Object will be returned:
    ```json
    {
        "info": "PAF test-api Version 1.0"
    }
    ```
-   Similar for `GET /api/users`

-   By requesting `GET /api/users/{id}` with `id` beeing a integer, a JSON-object, beeing either a user-object or a error-object, is returned. The http-response-code is set to either 200 (ok) or 500 (error) (see [Response](#response)).

<hr>

## Classes

| Class                 | Documentation                                                             |
| --------------------- | ------------------------------------------------------------------------- |
| `PAF\Router\Router`   | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Router-Router.html)   |
| `PAF\Router\Group`    | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Router-Group.html)    |
| `PAF\Router\Route`    | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Router-Route.html)    |
| `PAF\Router\Response` | [Link](https://m-thalmann.github.io/PAF/classes/PAF-Router-Response.html) |

<hr>

## Initialization

The router has to be initialized before using it:

```php
Router::init(string $basePath, boolean $corsEnabled);
```

The `$basePath` is the prefix of every route.

If you set `$corsEnabled` to true, the appropriate headers are set, so cors-requests are possible (default: false).

**IMPORTANT:** All headers _must_ be set before calling `Router::init(...)`

**Tipp:** If you want cors to work and you pass a authorization-header or a content-type header, you have to add a `Access-Control-Allow-Headers` header with the wanted headers as value (separated by ',').<br>
Example: `Access-Control-Allow-Headers: Content-Type, Authorization`.<br>
For that use the `Router::setHeader(string $name, string $value)` function.

### Headers

You can set http-headers, that will be set, when the router matches and executes a route:

```php
Router::setHeader(string $name, string $value);

Router::setHeaders(array $headers); // map, with key as name of the header and value as value
```

If you want to delete a header, set its value to `NULL`.

**IMPORTANT:** All headers _must_ be set before calling `Router::init(...)`

### Ignore Query

You can set whether the query-part of the url should also be taken into account, when matching the path.<br>
**Default is true!**

```php
Router::setIgnoreQuery(boolean $ignoreQuery);
```

Example:

`$ignoreQuery = TRUE`:<br>
Route `/users` matches with `/users?limit=1&offset=3`

`$ignoreQuery = FALSE`:<br>
Route `/users` _does not_ match with `/users?limit=1&offset=3`

<hr>

## Adding routes

If you want to add routes to the base path you have to call the `Router::addRoutes()` function and then call the functions for adding routes.<br>
There are several methods for adding routes:

```php
all($path, ...$targets) // any method

get($path, ...$targets)
head($path, ...$targets)
post($path, ...$targets)
put($path, ...$targets)
delete($path, ...$targets)

map($method, $path, ...$targets) // method: any http-request-method (in caps) or '*' for any
```

Example:

```php
Router::addRoutes()
    ->get('/', function($req){
        // ...
        return $response;
    })
    ->delete('/', function($req){
        // ...
        return NULL;
    })
    ->map('PATCH', '/' function($req){
        // ...
        return NULL;
    });
```

These functions can be chained, so you can add multiple routes to a group or the base path (fluent api).

**IMPORTANT:** The order of the function calls matters. The first found match is selected!

### Path

The **path** of a route can contain regex, but no groups, since they would lead to unwanted behaviour. Therefore please ommit the `(` and `)` characters!
Alternatively use non capturing groups: `(?:<...>)`

The **path** can also contain parameters:

```php
Router::addRoutes()->get('/user/{{i:id}}', function ($req) {
    $id = $req['params']['id'];
});
```

The syntax for a parameter is the following: `{{<type>:<name>}}`

The type can be:

-   ` ` (_empty_), `s` or `*` for a string
-   `i` for a integer
-   `n` for any number (also with decimal; for example: `12.2`)

\[ ! \] The route only matches if the type of the parameter matches with the given parameter.<br>

The parameters get stored inside of the `$request['params']` map, with their name as the key. The values are converted to the according types (see [Targets](#targets)).

### Targets

When adding routes, you have to define (at least) one function, that is executed once this route is matched. You can also define multiple target-functions, but only the first one is executed directly by the router (the others have to be called within the target-function(s)).

```php
get('/users', function($req, $next){
    return $next($req['path']); // call the next target function with a parameter (max. 1 parameter)
}, function($data, $next){
    return $next($data);        // -"-
}, function($data)){
    return substr($data, 0, 2);
});
```

Only the return value of the first target-function is used for the output of the api, so make sure you return the correct value.

Every target function receives two parameters: The data passed from the previous target function and the next target function (= null, if there is no more target function).

#### Request

The first target function receives a **request-array** as the first parameter:

```php
[
    'route' => Route,       // matched route object
    'method' => string,     // http-request-method
    'url' => string,        // full request url
    'path' => string,       // matched path of the route
    'authorization' => string|null // contains the content of the authorization header if it is set
    'params' => map,        // map containing all parameters of the path (converted to datatype)
    'post' => mixed|null,   // posted data, if data was posted (not formdata -> use $_POST)
]
```

<hr>

## Groups

Groups are used to group routes, that have same prefix, together. A group can contain routes and other groups.

Adding groups to the base path:

```php
Router::group('/user'); // access the group object here (add routes, ...) (fluent api)
```

Adding routes to a group:

```php
Router::group('/user')
    ->get('/', function () {
        // ...
        return $response;
    })
    ->post('/', function ($req) {
        // ...
        return $response;
    });
```

Adding groups to a group:

```php
Router::group('/users')->group('/home'); // ...
```

**IMPORTANT:** The order of the groups matters. The first found match is selected!

### Lazy groups

A lazy group loads the groups and routes contained by this group only when the group is matched. This improves the performance, since no files, that aren't needed, are loaded.

To do this you have to pass a path (preferably absolute) to the group. Don't add any routes and groups here; do that inside of the file, the path points to:

`index.php`:

```php
Router::group('/users', __DIR__ . '/groups/users.php');
```

`groups/users.php`:

```php
$group
    ->get('/', function () {
        // ...
        return $response;
    })
    ->post('/', function ($req) {
        // ...
        return $response;
    });
```

If you want to change the name of the variable (`$group`) you can do that by passing the name for it to the group function:

```php
Router::group('/users', __DIR__ . '/groups/users.php', 'myGroupVariable'); // access via $myGroupVariable
```

<hr>

## Response

The response of the first target function is used as the output of the api. If you just return a value, it will be converted to JSON and then output. The http-response-code will be 200.

When converting the response to JSON, the value is an object (other than `Response`) and has a function called
`toJSON()`, it (the `toJSON()`-function) will be executed and the output of that function will be used, otherwise the value will be used as is. Then the value will be encoded to JSON with `json_encode(...)`.<br>
If the value is an array (also within any object), the same is done for every element.

**WARNING:** The usage of `toJSON` is deprecated. Implement the _JsonSerializable_ interface instead: https://www.php.net/manual/en/jsonserializable.jsonserialize.php.

```php
[...]

// WARN: deprecated version (use version below)
class User{
    private $name = null;

    [...]

    /**
     * @deprecated use JsonSerializable instead
     */
    function toJSON(){
        return [
            "name" => $this->name
        ];
    }
}

// new version
class User implements JsonSerializable{
    private $name = null;

    [...]

    public function jsonSerialize(){
        return [
            "name" => $this->name
        ];
    }
}

[...]

$router->map('GET', '/user', function(){
    return new User('Foo'); // toJSON() or jsonSerialize() will be executed
});
```

If you want to change the http-response-code or/and the content-type of the response, you have to use the `Response`-object:

```php
$response = new Response(mixed $value, integer $httpCode, string $contentType);
```

When changing the content-type of a response to anything different than `application/json`, the value will _not_ be converted to JSON (obviously).

You can also easily create response objects with the correct http-response-code by using the static creation functions:

```php
Response::ok($value, $contentType); // Code: 200
Response::created($value, $contentType); // Code: 201
Response::noContent($value, $contentType); // Code: 204

Response::badRequest($value, $contentType); // Code: 400
Response::unauthorized($value, $contentType); // Code: 401
Response::forbidden($value, $contentType); // Code: 403
Response::notFound($value, $contentType); // Code: 404
Response::methodNotAllowed($value, $contentType); // Code: 405
Response::conflict($value, $contentType); // Code: 409
Response::tooManyRequests($value, $contentType); // Code: 429

Response::error($value, $contentType); // Code: 500
Response::notImplemented($value, $contentType); // Code: 501
```

Like this you can simply return the created response:

```php
$group->get('/users/{{i:id}}/image', function () {
    // ...

    if ($notFound) {
        return Response::notFound('User was not found'); // default content-type: 'application/json'
    } else {
        return Response::ok($userImage, 'image/jpeg');
    }
});
```

<hr>

## Output

If you want to output something, without the router outputting anything (for example if Router::execute() returns `FALSE`, which means there was no route matched), you can use the `Router::output($value);` function.<br>
This function is normally used by the router to output the return value of the matched route, and therefore behaves exactly like described before.

<hr>

## Examples

Info: Make sure to copy the needed files into the example's folder (.htaccess, JWT.php, ...)

-   [Authorization](https://github.com/m-thalmann/PAF/tree/master/examples/Router/Authorization)
-   [Sample API Structure](https://github.com/m-thalmann/PAF/tree/master/examples/Router/Sample-API-Structure)
