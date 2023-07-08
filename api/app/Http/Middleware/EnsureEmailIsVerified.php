<?php

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedHttpException;
use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        if (!$request->user() || !$request->user()->hasVerifiedEmail()) {
            throw UnauthorizedHttpException::unverified(
                __('messages.email_must_be_verified')
            );
        }

        return $next($request);
    }
}
