<?php
/**
 * This class implements a simple way to en-/decode JWT-Tokens.
 * When decoding a token, and something is not valid, it throws a according
 * error.
 *
 * @author Matthias Thalmann {https://github.com/m-thalmann}
 * @license MIT
 * @version 2.0
 */
class JWT {
    /**
     * @var array $algorithms The supported algorithms
     */
    private static $algorithms = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    ];

    /**
     * Should not be instanciable
     */
    private function __construct() {
    }

    /**
     * Encodes the JWT-payload (body), signs it with the algorithm specified (default HS256)
     * and returns it
     *
     * @param string $secret The secret for generating the signature
     * @param array $payload The body of the JWT-Token
     * @param string $alg The algorithm to sign the token
     *
     * @return string The token
     *
     * @throws InvalidArgumentException
     */
    public static function encode($secret, $payload, $alg = 'HS256') {
        if (!is_array($payload)) {
            throw new InvalidArgumentException('Payload has wrong format');
        }

        if (!array_key_exists('iat', $payload)) {
            $payload['iat'] = time();
        }

        $payload = json_encode($payload);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Payload has wrong format');
        }

        if (!is_string($alg) || !array_key_exists($alg, JWT::$algorithms)) {
            throw new InvalidArgumentException('Algorithm not supported');
        }

        $header = [
            "alg" => $alg,
            "typ" => 'JWT',
        ];

        $header = json_encode($header);

        $header = JWT::safeBase64Encode($header);

        $payload = JWT::safeBase64Encode($payload);

        $signature = self::sign($secret, $alg, $header, $payload);

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Decodes the JWT-token and returns the payload (body)
     *
     * @param string $secret The secret for generating the signature
     * @param string $token The JWT-Token
     * @param boolean $noValidate Sets whether the signature should be validated
     *
     * @return array The payload (body)
     *
     * @throws InvalidArgumentException
     * @throws MalformedException
     * @throws SignatureInvalidException
     * @throws BeforeValidException
     * @throws ExpiredException
     */
    public static function decode($secret, $token, $noValidate = false) {
        if (!is_string($token)) {
            throw new InvalidArgumentException('Token must be string');
        }

        $fragments = explode('.', $token);

        if (count($fragments) != 3) {
            throw new MalformedException('Fragments-length is not correct');
        }

        list($_header, $_payload) = $fragments;

        for ($i = 0; $i < 2; $i++) {
            $fragments[$i] = json_decode(
                JWT::safeBase64Decode($fragments[$i]),
                true
            );

            if (json_last_error() != JSON_ERROR_NONE) {
                throw new MalformedException(
                    'Fragments have incompatible format'
                );
            }
        }

        list($header, $payload, $signature) = $fragments;

        if (
            !array_key_exists('typ', $header) ||
            !array_key_exists('alg', $header) ||
            $header['typ'] != 'JWT'
        ) {
            throw new MalformedException('Header-information is wrong');
        }

        if (!array_key_exists($header['alg'], JWT::$algorithms)) {
            throw new InvalidArgumentException('Algorithm not supported');
        }

        if ($noValidate !== true) {
            if (
                self::sign($secret, $header['alg'], $_header, $_payload) !=
                $signature
            ) {
                throw new SignatureInvalidException('Signature is not valid');
            }
        }

        $now = time();

        if (array_key_exists('iat', $payload) && $now < $payload['iat']) {
            throw new BeforeValidException('Issued timestamp is in the future');
        }

        if (array_key_exists('nbf', $payload) && $now < $payload['nbf']) {
            throw new BeforeValidException('Token is not yet valid');
        }

        if (array_key_exists('exp', $payload) && $now > $payload['exp']) {
            throw new ExpiredException('Token has expired');
        }

        return $payload;
    }

    private static function safeBase64Encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function safeBase64Decode($data) {
        return base64_decode(
            str_pad(
                strtr($data, '-_', '+/'),
                strlen($data) % 4,
                '=',
                STR_PAD_RIGHT
            )
        );
    }

    /**
     * Generates the signature
     *
     * @param string $secret The secret for generating the signature
     * @param string $alg The algorithm for the signature
     * @param string $header The base64urlEncoded header
     * @param string $payload The base64urlEncoded payload (body)
     *
     * @return string The signature
     */
    private static function sign($secret, $alg, $header, $payload) {
        $signature = hash_hmac(
            JWT::$algorithms[$alg],
            $header . '.' . $payload,
            $secret,
            true
        );
        return JWT::safeBase64Encode($signature);
    }
}

/*
 * Exception classes for catching different errors
 */

class JWTException extends RuntimeException {
}
class MalformedException extends JWTException {
}
class SignatureInvalidException extends JWTException {
}
class ExpiredException extends JWTException {
}
class BeforeValidException extends JWTException {
}
?>
