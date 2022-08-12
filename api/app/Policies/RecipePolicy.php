<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\RecipeCollection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecipePolicy {
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
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(?User $user, Recipe $recipe) {
        $isUserOwnerOrRecipePublic =
            optional($user)->id === $recipe->user_id || $recipe->is_public;

        if (
            $isUserOwnerOrRecipePublic ||
            $user === null ||
            $recipe->recipe_collection_id === null
        ) {
            return $isUserOwnerOrRecipePublic;
        }

        return RecipeCollection::query()
            ->where('id', $recipe->recipe_collection_id)
            ->forUser($user, mustBeAdmin: false)
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
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Recipe $recipe) {
        if ($user->id === $recipe->user_id) {
            return true;
        }

        return RecipeCollection::query()
            ->where('id', $recipe->recipe_collection_id)
            ->forUser($user, mustBeAdmin: true)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Recipe $recipe) {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Recipe $recipe) {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Recipe $recipe) {
        return $user->id === $recipe->user_id;
    }
}

