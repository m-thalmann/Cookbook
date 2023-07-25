<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler {
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register() {
    }

    public function render($request, Throwable $e) {
        if ($e instanceof ModelNotFoundException) {
            throw new NotFoundHttpException();
        }

        if ($e instanceof ThrottleRequestsException) {
            $e = new ThrottleRequestsException(
                __('messages.http.too_many_requests'),
                $e->getPrevious(),
                $e->getHeaders(),
                $e->getCode()
            );
        }

        if ($e instanceof AuthenticationException) {
            $e = new AuthenticationException(
                __('messages.http.unauthorized'),
                $e->guards(),
                $e->redirectTo()
            );
        }

        if ($e instanceof InvalidSignatureException) {
            $e = new HttpException(403, __('messages.http.invalid_signature'));
        }

        if ($e instanceof PDOException && !config('app.debug')) {
            $e = new HttpException(500, __('messages.database_error'));
        }

        return parent::render($request, $e);
    }
}
