<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            $unverifiedResponse = [
                'message' => __('messages.email_must_be_verified'),
                'meta' => 'unverified',
            ];

            return response()->json($unverifiedResponse, 401);
        }

        return $next($request);
    }
}

