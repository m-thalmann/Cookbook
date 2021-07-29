# PHP-JWT

This class implements a simple way to en-/decode JWT-Tokens

## Usage

```php
require_once '/path/to/JWT.php';

$secret = '...'; // contains your secret

$token = JWT::encode($secret, [
    "name" => "Max Mustermann",
    "nbf" => time() + 120, // Valid in 2 minutes
]);

try {
    $payload = JWT::decode($secret, $token);

    print_r($payload);
} catch (Exception $e) {
    if ($e instanceof JWTException) {
        echo "The token is not valid";
    }
}
```

## Exception-Classes

### JWTException

#### MalformedException extends JWTException

The token has a wrong format

#### SignatureInvalidException extends JWTException

The token's signature is wrong

#### ExpiredException extends JWTException

The token has expired

#### BeforeValidException extends JWTException

The token is not yet valid
