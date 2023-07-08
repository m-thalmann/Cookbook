<?php

namespace App\Exceptions;

class UnauthorizedHttpException extends HttpException {
    /**
     * @var string The email was not yet verified
     */
    const REASON_UNVERIFIED = 'unverified';
    /**
     * @var string The credentials did not match
     */
    const REASON_CREDENTIALS = 'credentials';

    /**
     * @var string[] The available reasons
     */
    const REASONS = [self::REASON_UNVERIFIED, self::REASON_CREDENTIALS];

    public function __construct(?string $message, ?string $reason) {
        $additional = [];

        if ($reason !== null) {
            $additional['reason'] = $reason;
        }

        if ($message === null) {
            $message = __('messages.http.unauthorized');
        }

        parent::__construct(401, $message, $additional);
    }

    /**
     * Creates a new UnauthorizedHttpException with the 'unverified'-reason
     *
     * @param string|null $message
     *
     * @return self
     */
    public static function unverified(?string $message) {
        return new self($message, self::REASON_UNVERIFIED);
    }

    /**
     * Creates a new UnauthorizedHttpException with the 'credentials'-reason
     *
     * @param string|null $message
     *
     * @return self
     */
    public static function credentials(?string $message) {
        return new self($message, self::REASON_CREDENTIALS);
    }
}
