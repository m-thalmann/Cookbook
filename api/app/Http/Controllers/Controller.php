<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Authorize a given action for the current user.
     * Instead of throwing an authorization exception
     * it throws a not found exception.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @see \Illuminate\Routing\Controller::authorize()
     * @see
     *
     * @throws \Illuminate\Auth\Access\NotFoundHttpException
     */
    public function authorizeAnonymously($ability, $arguments = []) {
        try {
            return $this->authorize($ability, $arguments);
        } catch (AuthorizationException $e) {
            throw new NotFoundHttpException();
        }
    }
}
