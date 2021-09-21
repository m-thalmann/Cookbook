<?php

namespace API\inc;

use PAF\Router\Response;

class ApiException extends \Exception implements \JsonSerializable {
    const ERRORS = [];

    private $type;
    private $errorValue;
    private $details = null;

    public function __construct($type, $errorKey, $details = null) {
        $this->type = $type;

        $this->errorValue = self::ERRORS[$type][$errorKey] ?? [
            "details" => $details,
        ];

        $this->errorValue["errorKey"] = "$type.$errorKey";

        $this->details = $details;

        $this->message = $details;
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
        return [
            "errorKey" => $this->errorValue["errorKey"],
            "details" =>
                $this->details ??
                ($this->errorValue["details"] ?? "An error occurred"),
        ];
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
