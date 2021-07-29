<?php

namespace PAF\Router;

/**
 * This class defines a Response
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class Response implements \JsonSerializable {
    /**
     * @var mixed The value of the response that should be displayed
     */
    public $value = null;

    /**
     * @var int The http-response-code of the response
     */
    public $code = 200;

    /**
     * @var string the content-type of the response
     */
    public $contentType = 'application/json';

    /**
     * Creates Response
     *
     * @param mixed $value
     * @param int $code
     * @param string $contentType
     */
    public function __construct(
        $value = null,
        $code = 200,
        $contentType = 'application/json'
    ) {
        $this->value = $value;
        $this->code = $code;
        $this->contentType = $contentType;
    }

    /**
     * Sets the value of this response
     *
     * @param mixed $value
     *
     * @return $this The object itself, for further calls
     */
    public function value($value) {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets the code of this response
     *
     * @param int $code
     *
     * @return $this The object itself, for further calls
     */
    public function code($code) {
        $this->code = $code;
        return $this;
    }

    /**
     * Verifies if this object is correct
     *
     * @return bool true if correct, otherwise false
     */
    public function verify() {
        return !empty($this->code) && is_int($this->code);
    }

    /**
     * @see \JsonSerializable
     *
     * @return array The value of the response
     */
    public function jsonSerialize() {
        return $this->value;
    }

    /**
     * Returns whether the response is ok (2xx) or not (anything other)
     *
     * @param bool $strict Whether to check only if it is 200 (true) or any 2xx (false)
     *
     * @return bool
     */
    public function isOk($strict = false) {
        if ($strict) {
            return $this->code === 200;
        }

        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * Checks whether the response is ok or not and executes the according callback function
     * and returns its return value
     *
     * @see Response::isOk()
     *
     * @param callable $callable_ok Gets executed when the response is ok
     * @param callable|null $callable_error Gets executed when the response is not ok
     * @param bool|null $strict Whether to check only if it is 200 (true) or any 2xx (false)
     *
     * @return mixed The return value of the called callback function
     *               (or null if is is not ok and no error function was defined)
     */
    public function then(
        $callable_ok,
        $callable_error = null,
        $strict = false
    ) {
        if (
            !is_callable($callable_ok) ||
            ($callable_error !== null && !is_callable($callable_error))
        ) {
            throw new \InvalidArgumentException(
                'Arguments have to be callable'
            );
        }

        if ($this->isOk($strict)) {
            return $callable_ok($this);
        } else {
            if ($callable_error !== null) {
                return $callable_error($this);
            } else {
                return null;
            }
        }
    }

    /*
     * Helper functions to set http-response-code
     */

    /**
     * HTTP-Code: 200
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function ok(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 200, $contentType);
    }

    /**
     * HTTP-Code: 201
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function created(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 201, $contentType);
    }

    /**
     * HTTP-Code: 204
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function noContent(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 204, $contentType);
    }

    /**
     * HTTP-Code: 400
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function badRequest(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 400, $contentType);
    }

    /**
     * HTTP-Code: 401
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function unauthorized(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 401, $contentType);
    }

    /**
     * HTTP-Code: 403
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function forbidden(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 403, $contentType);
    }

    /**
     * HTTP-Code: 404
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function notFound(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 404, $contentType);
    }

    /**
     * HTTP-Code: 405
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function methodNotAllowed(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 405, $contentType);
    }

    /**
     * HTTP-Code: 409
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function conflict(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 409, $contentType);
    }

    /**
     * HTTP-Code: 429
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function tooManyRequests(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 429, $contentType);
    }

    /**
     * HTTP-Code: 500
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function error(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 500, $contentType);
    }

    /**
     * HTTP-Code: 501
     *
     * @param mixed $value
     * @param string $contentType
     * @return Response The created response
     */
    public static function notImplemented(
        $value = null,
        $contentType = 'application/json'
    ) {
        return new Response($value, 501, $contentType);
    }
}
