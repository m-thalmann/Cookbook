<?php

namespace App\Exceptions;

use Exception;

class HttpException extends Exception {
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
        public readonly array $additional = []
    ) {
        parent::__construct($message);
    }

    public function report() {
    }

    public function render($request) {
        $errorData = [
            'message' => $this->getMessage(),
            ...$this->additional,
        ];

        return response()->json($errorData, $this->statusCode);
    }
}
