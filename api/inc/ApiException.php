<?php

namespace API\inc;

use PAF\Router\Response;

class ApiException extends \Exception implements \JsonSerializable {
    const DEFAULT_MESSAGE = "An error occurred";

    private $type;
    private $errorValue;

    public function __construct($type, $errorKey, $details = null) {
        $details = $details ?? self::DEFAULT_MESSAGE;

        parent::__construct(
            is_string($details) ? $details : self::DEFAULT_MESSAGE
        );

        $this->type = $type;

        $this->errorValue = [
            "errorKey" => "$type.$errorKey",
            "details" => $details,
        ];
    }

    public static function badRequest($errorKey, $details = null) {
        return new self("bad_request", $errorKey, $details);
    }

    public static function unauthorized($errorKey, $details = null) {
        return new self("unauthorized", $errorKey, $details);
    }

    public static function forbidden($errorKey, $details = null) {
        return new self("forbidden", $errorKey, $details);
    }

    public static function methodNotAllowed($errorKey, $details = null) {
        return new self("method_not_allowed", $errorKey, $details);
    }

    public static function conflict($errorKey, $details = null) {
        return new self("conflict", $errorKey, $details);
    }

    public static function tooManyRequests($errorKey, $details = null) {
        return new self("too_many_requests", $errorKey, $details);
    }

    public static function error($errorKey, $details = null) {
        return new self("error", $errorKey, $details);
    }

    public static function notImplemented($errorKey, $details = null) {
        return new self("not_implemented", $errorKey, $details);
    }

    public function jsonSerialize() {
        return $this->errorValue;
    }

    public function getResponse() {
        return new Response(
            $this->jsonSerialize(),
            $this->errorValue["code"] ?? self::parseCodeFromType($this->type)
        );
    }

    private static function parseCodeFromType($type) {
        switch ($type) {
            case "bad_request":
                return 400;
            case "unauthorized":
                return 401;
            case "forbidden":
                return 403;
            case "method_not_allowed":
                return 405;
            case "conflict":
                return 409;
            case "too_many_requests":
                return 429;
            case "not_implemented":
                return 501;
            default:
                return 500;
        }
    }
}
