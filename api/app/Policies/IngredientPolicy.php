<?php

namespace App\Policies;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientPolicy {
    use HandlesAuthorization;

    public function before(User $user, $ability) {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ingredient  $ingredient
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Ingredient $ingredient) {
        return $user->can('update', $ingredient->recipe);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Ingredient  $ingredient
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Ingredient $ingredient) {
        return $user->can('delete', $ingredient->recipe);
    }
}

