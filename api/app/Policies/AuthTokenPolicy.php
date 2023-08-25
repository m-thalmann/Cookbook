<?php

namespace App\Policies;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthTokenPolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AuthToken  $authToken
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AuthToken $authToken) {
        return $user->id === $authToken->authenticatable_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AuthToken  $authToken
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AuthToken $authToken) {
        return $user->id === $authToken->authenticatable_id;
    }
}
