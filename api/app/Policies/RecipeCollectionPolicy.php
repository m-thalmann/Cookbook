<?php

namespace App\Policies;

use App\Models\RecipeCollection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecipeCollectionPolicy {
    use HandlesAuthorization;

    public function before(User $user, $ability) {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user) {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RecipeCollection  $recipeCollection
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, RecipeCollection $recipeCollection) {
        return $recipeCollection
            ->users()
            ->where('id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user) {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RecipeCollection  $recipeCollection
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, RecipeCollection $recipeCollection) {
        return $this->admin($user, $recipeCollection);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RecipeCollection  $recipeCollection
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, RecipeCollection $recipeCollection) {
        return $this->admin($user, $recipeCollection);
    }

    /**
     * Determine whether the user can administrate the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RecipeCollection  $recipeCollection
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function admin(User $user, RecipeCollection $recipeCollection) {
        return $recipeCollection
            ->users()
            ->wherePivot('is_admin', true)
            ->where('user_id', $user->id)
            ->exists();
    }
}

